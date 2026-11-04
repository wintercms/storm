<?php

namespace Winter\Storm\Foundation\Extension;

interface WinterExtension
{
    public function getPath(): string;

    public function getVersion(): string;

    public function getIdentifier(): string;

    public function getComposerPackageName(): ?string;

    public function getComposerPackageVersion(): ?string;
}
