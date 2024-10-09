<?php

namespace Winter\Storm\Tests\Database\Fixtures;

use Illuminate\Database\Schema\Builder;

/**
 * Allow model fixtures in tests to migrate their own schema.
 *
 * @author Ben Thomson <git@alfreido.com>
 * @copyright Winter CMS
 */
trait MigratesForTesting
{
    /**
     * Store the models that have been migrated.
     */
    public static $migrated = false;

    /**
     * Migrate the schema up for the model.
     */
    public static function migrateUp(Builder $builder): void
    {
    }

    /**
     * Migrate the schema down for the model.
     */
    public static function migrateDown(Builder $builder): void
    {
    }
}
