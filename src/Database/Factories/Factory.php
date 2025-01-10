<?php

namespace Winter\Storm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory as BaseFactory;
use Illuminate\Support\Str;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @method $this trashed()
 */
abstract class Factory extends BaseFactory
{
    /**
     * Get the factory name for the given model name.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelName
     * @return class-string<\Illuminate\Database\Eloquent\Factories\Factory>
     */
    public static function resolveFactoryName(string $modelName)
    {
        $pluginNamespace = Str::before($modelName, 'Models');
        $modelClassName = Str::after($modelName, 'Models\\');

        return $pluginNamespace.static::$namespace.$modelClassName.'Factory';
    }
}
