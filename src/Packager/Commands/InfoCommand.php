<?php

namespace Winter\Storm\Packager\Commands;

use Illuminate\Support\Facades\Cache;
use System\Classes\Packager\Composer;
use Winter\Packager\Commands\BaseCommand;
use Winter\Packager\Exceptions\CommandException;
use Winter\Packager\Exceptions\WorkDirException;

class InfoCommand extends BaseCommand
{
    protected ?string $package = null;

    /**
     * Command handler.
     *
     * @param string|null $package
     * @param boolean $dryRun
     * @param boolean $dev
     * @return void
     * @throws CommandException
     */
    public function handle(?string $package = null): void
    {
        $this->package = $package;
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

        $result = json_decode($message, JSON_OBJECT_AS_ARRAY);

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
}
