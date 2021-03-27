<?php namespace Winter\Storm\Support\Facades;

use Winter\Storm\Support\Facade;

/**
 * @method static bool has(string $key)
 * @method static bool hasGroup(string $key)
 * @method static mixed get(array|string $key, $default = null)
 * @method static array all()
 * @method static void set(array|string $key, $value)
 * @method static void prepend(string $key, $value)
 * @method static void push(string $key, $value)
 * @method static array parseConfigKey(string $key)
 * @method static void package(string $namespace, string $hint)
 * @method static void afterLoading(string $namespace, \Closure $callback)
 * @method static void addNamespace(string $namespace, string $hint)
 * @method static array getNamespaces()
 * @method static \Winter\Storm\Config\LoaderInterface getLoader()
 * @method static void setLoader(\Winter\Storm\Config\LoaderInterface $loader)
 * @method static string getEnvironment()
 * @method static array getAfterLoadCallbacks()
 * @method static array getItems()
 * @method static bool offsetExists(string $key)
 * @method static mixed offsetGet(string $key)
 * @method static void offsetSet(string $key, mixed $value)
 * @method static void offsetUnset(string $key)
 *
 * @see \Winter\Storm\Config\Repository
 */
class Config extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'config';
    }
}
