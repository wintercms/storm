<?php namespace Winter\Storm\Foundation\Console;

use Illuminate\Foundation\Console\ClearCompiledCommand as ClearCompiledCommandBase;

/**
 * @property \Winter\Storm\Foundation\Application $laravel
 */
class ClearCompiledCommand extends ClearCompiledCommandBase
{
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (file_exists($classesPath = $this->laravel->getCachedClassesPath())) {
            @unlink($classesPath);
        }

        parent::handle();
    }
}
