<?php

namespace Winter\Storm\Support\Traits;

use Winter\Storm\Packager\Composer;

trait HasComposerPackage
{
    /**
     * @var ?array The composer package details for this plugin.
     * [
     *  'name' => '',
     * ]
     */
    protected ?array $composerPackage = null;

    /**
     * Get the composer package details
     */
    protected function getComposerPackage(): ?array
    {
        return $this->composerPackage ?? $this->composerPackage = Composer::getPackageInfoByExtension($this);
    }

    /**
     * Get the composer package name
     */
    public function getComposerPackageName(): ?string
    {
        return $this->getComposerPackage()['name'] ?? null;
    }

    /**
     * Get the composer package version
     */
    public function getComposerPackageVersion(): ?string
    {
        return $this->getComposerPackage()['version'] ?? null;
    }
}
