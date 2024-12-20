<?php

namespace Winter\Storm\Support\Traits;

trait HasComposerPackage
{
    /**
     * @var ?array The composer package details for this plugin.
     */
    protected ?array $composerPackage = null;

    /**
     * Set the composer package property for the plugin
     */
    public function setComposerPackage(?array $package): void
    {
        $this->composerPackage = $package;
    }

    /**
     * Get the composer package details
     */
    public function getComposerPackage(): ?array
    {
        return $this->composerPackage;
    }

    /**
     * Get the composer package name
     */
    public function getComposerPackageName(): ?string
    {
        return $this->composerPackage['name'] ?? null;
    }
}
