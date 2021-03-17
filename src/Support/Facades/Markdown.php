<?php namespace Winter\Storm\Support\Facades;

use Winter\Storm\Support\Facade;

/**
 * @method static string parse(string $text)
 * @method static string parseClean(string $text)
 * @method static string parseSafe(string $text)
 * @method static string parseLine(string $text)
 *
 * @see \Winter\Storm\Parse\Markdown
 */
class Markdown extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'parse.markdown';
    }
}
