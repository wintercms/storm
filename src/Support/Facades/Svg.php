<?php namespace Winter\Storm\Support\Facades;

use Winter\Storm\Support\Facade;

/**
 * @method static string extract(string $path, bool $minify = true)
 *
 * @see \Winter\Storm\Support\Svg
 */
class Svg extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'svg';
    }
}
