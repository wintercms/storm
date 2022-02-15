<?php namespace Winter\Storm\Database;

use Illuminate\Database\MigrationServiceProvider as LaravelServiceProvider;

class MigrationServiceProvider extends LaravelServiceProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        // Don't register any Laravel provided migration commands by default
    ];
}
