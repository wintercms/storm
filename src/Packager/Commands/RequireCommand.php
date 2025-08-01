<?php

namespace Winter\Storm\Packager\Commands;

use Illuminate\Support\Facades\Cache;
use Winter\Storm\Packager\Composer;
use Winter\Packager\Commands\BaseCommand;
use Winter\Packager\Exceptions\CommandException;
use Winter\Packager\Exceptions\WorkDirException;

class RequireCommand extends BaseCommand
{
    protected ?string $package = null;
    protected bool $dryRun = false;
    protected bool $dev = false;
    protected bool $returnRequired = false;

    /**
     * Command handler.
     *
     * @param string|null $package
     * @param boolean $dryRun
     * @param boolean $dev
     * @return void
     * @throws CommandException
     */
    public function handle(
        ?string $package = null,
        bool $dryRun = false,
        bool $dev = false,
        bool $returnRequired = false
    ): void {
        if (!$package) {
            throw new CommandException('Must provide a package');
        }

        $this->package = $package;
        $this->dryRun = $dryRun;
        $this->dev = $dev;
        $this->returnRequired = $returnRequired;
    }

    /**
     * @inheritDoc
     */
    public function arguments(): array
    {
        $arguments = [];

        if ($this->dryRun) {
            $arguments['--dry-run'] = true;
        }

        if ($this->dev) {
            $arguments['--dev'] = true;
        }

        $arguments['packages'] = [$this->package];

        return $arguments;
    }

    /**
     * @throws CommandException
     * @throws WorkDirException
     */
    public function execute(): string
    {
        $output = $this->runComposerCommand();
        $message = implode(PHP_EOL, $output['output']);

        if ($output['code'] !== 0) {
            throw new CommandException($message);
        }

        if (!$this->dryRun) {
            Cache::forget(Composer::COMPOSER_CACHE_KEY);
        }

        if ($this->returnRequired) {
            preg_match('/Using version (.*?) /', $output['output'][count($output['output']) - 1], $matches);
            return $matches[1] ?? throw new CommandException('Unable to determine required version');
        }

        return $message;
    }

    /**
     * @inheritDoc
     */
    public function getCommandName(): string
    {
        return 'require';
    }

    public function requiresWorkDir(): bool
    {
        return true;
    }
}
