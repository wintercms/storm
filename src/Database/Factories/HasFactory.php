<?php

namespace Winter\Storm\Database\Factories;

trait HasFactory
{
    /**
     * Get a new factory instance for the model.
     */
    public static function factory(callable|array|int|null $count = null, callable|array $state = []): Factory
    {
        $factory = static::newFactory() ?: Factory::factoryForModel(get_called_class());

        return $factory
            ->count(is_numeric($count) ? $count : null)
            ->state(is_callable($count) || is_array($count) ? $count : $state);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Winter\Storm\Database\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        //
    }
}
