<?php

namespace Winter\Storm\Packager\Commands;

use Winter\Packager\Commands\Show;
use Winter\Packager\Enums\ShowMode;

class ShowCommand extends Show
{
    protected bool $path = false;

    /**
     * Command handler.
     *
     * The mode can be one of the following:
     *  - `installed`: Show installed packages
     *  - `locked`: Show locked packages
     *  - `platform`: Show platform requirements
     *  - `available`: Show all available packages
     *  - `self`: Show the current package
     *  - `path`: Show the package path
     *  - `tree`: Show packages in a dependency tree
     *  - `outdated`: Show only outdated packages
     *  - `direct`: Show only direct dependencies
     *
     * @param string|null $mode
     * @param string|null $package
     * @param boolean $noDev
     * @param boolean $path
     * @return void
     */
    public function handle(string|ShowMode $mode = 'installed', ?string $package = null, bool $noDev = false, bool $path = false): void
    {
        $this->mode = is_string($mode) ? ShowMode::from($mode) : $mode;
        $this->package = $package;
        $this->path = $path;
        $this->noDev = $noDev;
    }

    /**
     * @inheritDoc
     */
    public function arguments(): array
    {
        $arguments = [];

        if (!empty($this->package)) {
            $arguments['package'] = $this->package;
        }

        if ($this->mode !== 'installed') {
            $arguments['--' . $this->mode->value] = true;
        }

        if ($this->noDev) {
            $arguments['--no-dev'] = true;
        }

        if ($this->path) {
            $arguments['--path'] = true;
        }

        $arguments['--format'] = 'json';

        return $arguments;
    }
}
