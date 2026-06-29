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

            if ($contextDir !== null && PathResolver::within($resolved, $contextDir)) {
                return [$resolved, dirname($filename)];
            }

            foreach ($allowedRoots as $root) {
                if (PathResolver::within($resolved, $root)) {
                    return [$resolved, dirname($filename)];
                }
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

        $key = rtrim($sourceDir, '/\\') . '/';

        return [$key => self::makeResolver($allowedRoots, $sourceDir)];
    }

}
