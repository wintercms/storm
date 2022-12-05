<?php namespace Winter\Storm\Support\Traits;

use Illuminate\Contracts\Container\Container;

/**
 * Singleton trait.
 *
 * Allows a simple interface for treating a class as a singleton.
 * Usage: myObject::instance()
 *
 * @author Alexey Bobkov, Samuel Georges, Luke Towers
 */
trait Singleton
{
    /**
     * Create a new instance of this singleton.
     */
    final public static function instance(?Container $container = null): static
    {
        if (!$container) {
            $container = app();
        }

        if (!$container->bound(static::class)) {
            $container->singleton(static::class, function () {
                return new static;
            });
        }

        return $container->make(static::class);
    }

    /**
     * Forget this singleton's instance if it exists
     */
    final public static function forgetInstance(?Container $container = null): void
    {
        if (!$container) {
            $container = app();
        }

        if ($container->bound(static::class)) {
            $container->forgetInstance(static::class);
        }
    }

    /**
     * Constructor.
     */
    final protected function __construct()
    {
        $this->init();
    }

    /**
     * Initialize the singleton free from constructor parameters.
     */
    protected function init()
    {
    }

    public function __clone()
    {
        trigger_error('Cloning '.__CLASS__.' is not allowed.', E_USER_ERROR);
    }

    public function __wakeup()
    {
        trigger_error('Unserializing '.__CLASS__.' is not allowed.', E_USER_ERROR);
    }
}
