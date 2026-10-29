<?php

namespace Winter\Storm\Foundation\Console;

use Illuminate\Foundation\Console\ConfigClearCommand as BaseCommand;

class ConfigClearCommand extends BaseCommand
{
    /**
     * @var string The console command signature.
     */
    protected $signature = 'config:clear
        {env? : Which environment should be cleared?}
    ';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $configPath = $this->laravel->getCachedConfigPath();

        if ($this->argument('env')) {
            $configPath = realpath(
                dirname($this->laravel->getCachedConfigPath())
                . DIRECTORY_SEPARATOR
                . $this->argument('env')
                . '.config.php'
            );
        }

        $this->files->delete($configPath);
        $this->components->info('Configuration cache cleared successfully.');
    }
}
