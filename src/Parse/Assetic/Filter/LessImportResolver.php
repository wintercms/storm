<?php namespace Winter\Storm\Parse\Assetic\Filter;

use Winter\Storm\Filesystem\Filesystem;
use Winter\Storm\Filesystem\PathResolver;

/**
 * Safe import resolver for wikimedia/less.php's `Less_Parser`.
 *
 * `Less_Parser` resolves `@import` directives via `Less_FileManager::getFilePath()`,
 * which checks a list of candidate roots and falls back (in `Less_ImportVisitor`) to
 * the raw attacker-supplied path when no candidate matches. For `@import (inline)`,
 * that fallback reads the raw path with `file_get_contents()` and inlines the bytes
 * verbatim — enabling arbitrary file disclosure when untrusted CSS is parsed.
 *
 * less.php also auto-prepends the source file's directory to the import-dir list as
 * a path-form entry, which is checked with `file_exists()` *before* any user-supplied
 * callable. That branch resolves `..` traversal natively. To make our callable the
 * authoritative resolver, we have to collide-and-override the auto-added entry by
 * using its exact normalised key (`buildImportDirs()` does this).
 *
 * That auto-added entry is re-created for *every* file less.php parses, not just the
 * entry file, and it is also consulted by `data-uri()` / `image-size()`. Overriding
 * only the entry file's directory therefore leaves any `.less` imported from another
 * directory ungated. `makeResolver()` closes that by registering a resolver for each
 * directory it admits, so the collision follows the import graph.
 *
 * Usage shapes:
 *
 *   // parseFile()-based caller (e.g. theme asset compilation):
 *   $parser->SetImportDirs(LessImportResolver::buildImportDirs($sourceFile, $roots));
 *   $parser->parseFile($sourceFile);
 *
 *   // parse()-string caller with no source-file context (e.g. backend BrandSetting):
 *   $parser->SetImportDirs(['' => LessImportResolver::makeResolver([], null)]);
 *   $parser->parse($css);
 */
class LessImportResolver
{
    /**
     * Path of the bundled sentinel returned for any @import that is denied. The file
     * is empty, so when less.php inlines its contents nothing is emitted into the CSS.
     */
    public const SENTINEL_PATH = __DIR__ . '/empty.less';

    /**
     * Build a closure suitable for use as the *value* in a
     * `Less_Parser::SetImportDirs([$key => $closure])` call.
     *
     * The closure receives an `@import` filename. It:
     *   - if the filename is relative and `$contextDir` is set, joins them; otherwise
     *     treats the filename as absolute,
     *   - runs `realpath()` to collapse traversal and symlinks,
     *   - accepts the resolved path if it is a prefix-match of `realpath($contextDir)`
     *     (when set) or `realpath($root)` for any root in `$allowedRoots`,
     *   - on accept: returns `[$resolved, dirname($filename)]`,
     *   - on reject (or unresolvable): returns the sentinel pair so less.php's
     *     `?? [$path, $path]` fallback never reaches the attacker's raw path.
     *
     * Implicit-allowed-by-context-dir: when `$contextDir` is non-null, its subtree is
     * always allowed. This preserves legitimate same-tree `@import "partial.less"`
     * without requiring callers to also list contextDir as an explicit root. For
     * `parse()`-string callers, pass null to get true deny-all behavior.
     *
     * @param string[] $allowedRoots Additional roots beyond $contextDir. Pass [] to
     *   allow only same-tree imports (or nothing at all if $contextDir is also null).
     * @param string|null $contextDir Source file directory for resolving relative
     *   filenames and as the implicit allowed root.
     */
    public static function makeResolver(array $allowedRoots, ?string $contextDir = null): \Closure
    {
        $filesystem = new Filesystem();
        $sentinel = [self::SENTINEL_PATH, ''];

        return function ($filename) use ($allowedRoots, $contextDir, $filesystem, $sentinel) {
            if (!is_string($filename) || $filename === '') {
                return $sentinel;
            }

            // Use the context dir for relative paths; absolute paths resolve as-is.
            if ($filesystem->isAbsolutePath($filename)) {
                $candidate = $filename;
            } elseif ($contextDir !== null) {
                $candidate = $contextDir . '/' . $filename;
            } else {
                return $sentinel;
            }

            // Try the bare path first, then with `.less` appended. This mirrors
            // less.php's own path-form `Less_FileManager::getFilePath()` extension
            // fallback. Without it, skip()'s onceMap call (which uses the raw
            // pre-extension-append path) would always sentinel-out and break
            // import-once dedup for subsequent legitimate imports.
            $resolved = realpath($candidate);
            if ($resolved === false) {
                $resolved = realpath($candidate . '.less');
            }
            if ($resolved === false) {
                return $sentinel;
            }

            if (PathResolver::withinAny($resolved, array_merge([$contextDir], $allowedRoots))) {
                // less.php is about to make this file's directory "current", which
                // re-adds an unconfined path-form import dir for it. Claim that key
                // now so the gate keeps applying to the file's own imports and to
                // any data-uri() / image-size() call it makes.
                self::registerDir(dirname($resolved), array_merge([$contextDir], $allowedRoots));

                return [$resolved, dirname($filename)];
            }

            return $sentinel;
        };
    }

    /**
     * Build the SetImportDirs array for `parseFile()`-based compiles.
     *
     * Computes the exact key required to collide with and override less.php's
     * auto-added currentDirectory entry. less.php normalises that key by running
     * `realpath()` (via `AbsPath()`) on the source file then `dirname()`-ing it
     * with a trailing slash, then `rtrim('/\\') . '/'` inside `SetImportDirs()`.
     * Reproducing that exact normalisation here is what makes PHP `array_merge`
     * string-key semantics replace the auto-added entry with our callable.
     *
     * @return array<string, \Closure> Single-entry array ready for SetImportDirs().
     */
    public static function buildImportDirs(string $sourceFile, array $allowedRoots): array
    {
        $resolvedSource = realpath($sourceFile);
        $sourceDir = $resolvedSource !== false ? dirname($resolvedSource) : dirname($sourceFile);

        return [self::importDirKey($sourceDir) => self::makeResolver($allowedRoots, $sourceDir)];
    }

    /**
     * Register a resolver for `$dir` directly on the parser's import-dir list, so it
     * collides with the path-form entry less.php auto-adds while that directory is
     * the current one. Existing entries are left alone: the first resolver to claim
     * a directory is the one that admitted it, and re-registering would only widen
     * the allowed set.
     *
     * @param string[] $allowedRoots
     */
    public static function registerDir(string $dir, array $allowedRoots): void
    {
        $key = self::importDirKey($dir);

        if (!isset(\Less_Parser::$options['import_dirs'][$key])) {
            \Less_Parser::$options['import_dirs'][$key] = self::makeResolver($allowedRoots, $dir);
        }
    }

    /**
     * Reproduce the exact key less.php uses for a directory in its import-dir list.
     *
     * It normalises the key by running the file through `AbsPath()`/`WinPath()`
     * (backslash -> forward slash) and `dirname()`-ing it with a trailing slash, then
     * `SetImportDirs()` applies `rtrim('/\\') . '/'`. Reproducing that normalisation
     * exactly is what makes PHP `array_merge` string-key collision replace the
     * auto-added entry with our callable. Getting it wrong doesn't fail loudly — it
     * silently leaves the auto-added path-form entry matching first via `file_exists`,
     * which is a security regression, and it differs by platform (Windows).
     */
    public static function importDirKey(string $dir): string
    {
        return rtrim((new Filesystem())->normalizePath($dir), '/') . '/';
    }
}
