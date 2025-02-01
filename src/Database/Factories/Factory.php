<?php

namespace Winter\Storm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory as BaseFactory;
use Illuminate\Support\Str;

/**
 * @template TModel of \Winter\Storm\Database\Model
 *
 * @method $this trashed()
 */
abstract class Factory extends BaseFactory
{
    /**
     * Get the factory name for the given model name.
     *
     * @param  class-string<\Winter\Storm\Database\Model>  $modelName
     * @return class-string<\Winter\Storm\Database\Factories\Factory>
     */
    public static function resolveFactoryName(string $modelName)
    {
        if (Str::contains($modelName, 'Models\\')) {
            $baseNamespace = trim(Str::before($modelName, 'Models'), '\\');
            $modelClassName = trim(Str::after($modelName, 'Models'), '\\');
        } else {
            $baseNamespace = '';
            $modelClassName = $modelName;
        }

        return trim(implode('\\', [
            $baseNamespace,
            trim(static::$namespace, '\\'),
            $modelClassName.'Factory'
        ]), '\\');
    }
}
