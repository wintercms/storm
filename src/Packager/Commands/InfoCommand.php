<?php

namespace Winter\Storm\Packager\Commands;

use Winter\Packager\Commands\BaseCommand;
use Winter\Packager\Exceptions\CommandException;
use Winter\Packager\Exceptions\WorkDirException;

class InfoCommand extends BaseCommand
{
    protected ?string $package = null;
    protected bool $all = false;
    protected bool $latest = false;

    /**
     * Command handler.
     *
     * @param string|null $package
     * @param boolean $all
     * @return void
     * @throws CommandException
     */
    public function handle(?string $package = null, bool $all = false, bool $latest = false): void
    {
        $this->package = $package;
        $this->all = $all;
        $this->latest = $latest;
    }

    /**
     * @inheritDoc
     */
    public function arguments(): array
    {
        $arguments = [
            '--format' => 'json',
        ];

        if (!$this->package) {
            return $arguments;
        }

        $arguments['package'] = $this->package;

        if ($this->all) {
            $arguments['--all'] = true;
        }

        if ($this->latest) {
            $arguments['--latest'] = true;
        }

        return $arguments;
    }

    /**
     * @throws CommandException
     * @throws WorkDirException
     */
    public function execute(): array
    {
        $output = $this->runComposerCommand();
        $message = implode(PHP_EOL, $output['output']);

        if ($output['code'] !== 0) {
            throw new CommandException($message);
        }

        $result = json_decode($message, flags: JSON_OBJECT_AS_ARRAY);

        return $this->package
            ? $result ?? []
            : $result['installed'] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getCommandName(): string
    {
        return 'info';
    }

    public function requiresWorkDir(): bool
    {
        return false;
    }
}
