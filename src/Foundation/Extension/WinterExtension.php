<?php

namespace Winter\Storm\Foundation\Extension;

interface WinterExtension
{
    public function getPath(): string;

    public function getVersion(): string;

    public function getIdentifier(): string;

    public function setComposerPackage(?array $package): void;

    public function getComposerPackage(): ?array;

    public function getComposerPackageName(): ?string;
}
