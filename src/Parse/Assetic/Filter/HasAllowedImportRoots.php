<?php namespace Winter\Storm\Parse\Assetic\Filter;

/**
 * Shared storage for the additional import roots an asset-combiner filter is willing
 * to resolve imports into, beyond the source file's own directory subtree.
 *
 * Configured by the caller (typically `System\Classes\CombineAssets`) to permit
 * legitimate cross-tree imports — e.g. a plugin asset importing a module asset — while
 * still confining everything else. Consumed together with {@see ImportGuard}.
 */
trait HasAllowedImportRoots
{
    /**
     * Additional roots beyond the asset's own source directory that import directives
     * are allowed to resolve into. Defaults to none — the asset's own directory subtree
     * is always allowed implicitly by the confinement check.
     *
     * @var string[]
     */
    protected array $allowedImportRoots = [];

    /**
     * Configure additional roots that import directives may resolve into. The source
     * file's own directory is always allowed; this list adds cross-tree destinations.
     *
     * @param string[] $roots
     */
    public function setAllowedImportRoots(array $roots): void
    {
        $this->allowedImportRoots = $roots;
    }
}
