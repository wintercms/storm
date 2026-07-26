<?php namespace Winter\Storm\Parse\Assetic\Filter;

use Winter\Storm\Filesystem\PathResolver;

/**
 * Shared confinement policy for asset-combiner import directives.
 *
 * The combiner filters (`LessImportResolver` for LESS `@import`, `JavascriptImporter`
 * for the JS `=include`/`=require` directives, and — via a caller-supplied validator —
 * Assetic's `CssImportFilter` for CSS `@import`) all resolve an attacker-influenceable
 * path with `realpath()` and must answer the same question before inlining the file:
 * is the resolved path within a location we are willing to disclose?
 *
 * Centralising the decision here keeps that policy identical across every filter, so a
 * traversal that is blocked for LESS cannot slip through the JS or CSS importer. The
 * policy: a resolved path is permitted if it lies within the source file's own
 * directory subtree (`$contextDir`) or within any explicitly configured `$allowedRoots`.
 *
 * See GHSA-2223-f22x-24cq (JS importer LFI) and GHSA-58fp-mcx6-7qf9 (LESS importer LFI).
 */
class ImportGuard
{
    /**
     * Determine whether an already-resolved (canonical) filesystem path is permitted
     * for import.
     *
     * Callers must pass a path that has already been through `realpath()` /
     * `PathResolver::resolve()` so that `..` traversal and symlinks are collapsed
     * before the confinement check runs.
     *
     * @param string $resolvedPath Canonical path to the candidate import target.
     * @param string|null $contextDir Source file's own directory. When non-null its
     *   subtree is always allowed, preserving legitimate same-tree imports. Pass null
     *   for deny-unless-explicitly-rooted behaviour.
     * @param string[] $allowedRoots Additional roots to allow beyond $contextDir.
     */
    public static function isAllowed(string $resolvedPath, ?string $contextDir, array $allowedRoots): bool
    {
        if ($contextDir !== null && $contextDir !== '' && PathResolver::within($resolvedPath, $contextDir)) {
            return true;
        }

        foreach ($allowedRoots as $root) {
            if ($root !== null && $root !== '' && PathResolver::within($resolvedPath, $root)) {
                return true;
            }
        }

        return false;
    }
}
