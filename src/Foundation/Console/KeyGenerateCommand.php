<?php namespace Winter\Storm\Foundation\Console;

use Illuminate\Foundation\Console\KeyGenerateCommand as KeyGenerateCommandBase;
use Winter\Storm\Parse\EnvFile;

class KeyGenerateCommand extends KeyGenerateCommandBase
{
    /**
     * Write a new environment file with the given key.
     *
     * @param  string  $key
     * @return bool
     */
    protected function writeNewEnvironmentFileWith($key)
    {
        $env = EnvFile::open($this->laravel->environmentFilePath());
        $env->set('APP_KEY', $key);
        $env->write();
        return true;
    }

    /**
     * Confirm before proceeding with the action.
     *
     * This method only asks for confirmation in production.
     *
     * @param  string  $warning
     * @param  \Closure|bool|null  $callback
     * @return bool
     */
    public function confirmToProceed($warning = 'Application In Production!', $callback = null)
    {
        if ($this->hasOption('force') && $this->option('force')) {
            return true;
        }

        $this->alert('An application key is already set!');

        $confirmed = $this->confirm('Do you really wish to run this command?');

        if (!$confirmed) {
            $this->comment('Command Canceled!');

            return false;
        }

        return true;
    }
}
