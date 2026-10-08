<?php namespace Winter\Storm\Database;

use Illuminate\Database\MigrationServiceProvider as BaseMigrationServiceProvider;

class MigrationServiceProvider extends BaseMigrationServiceProvider
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
