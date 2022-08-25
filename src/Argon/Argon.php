<?php namespace Winter\Storm\Argon;

use Illuminate\Support\Carbon;

/**
 * Umbrella class.
 */
class Argon extends Carbon
{
    /**
     * Function to call instead of format.
     *
     * @var string|callable|null
     */
    protected static $formatFunction = 'translatedFormat';

    /**
     * Function to call instead of createFromFormat.
     *
     * @var string|callable|null
     */
    protected static $createFromFormatFunction = 'createFromFormatWithCurrentLocale';

    /**
     * Function to call instead of parse.
     *
     * @var string|callable|null
     */
    protected static $parseFunction = 'parseWithCurrentLocale';

    /**
     * Locale-aware parsing callback.
     *
     * This will ensure that the current locale is used when parsing dates.
     *
     * @throws \Carbon\Exceptions\InvalidFormatException If the format provided is invalid.
     */
    public static function parseWithCurrentLocale(
        string|\DateTimeInterface|null $time = null,
        string|\DateTimeZone|null $timezone = null
    ): static {
        if (is_string($time)) {
            $time = static::translateTimeString($time, static::getLocale(), 'en');
        }

        return parent::rawParse($time, $timezone);
    }

    /**
     * Locale-aware instance creation callback.
     *
     * This will ensure that the current locale is used when creating a new Argon/Carbon object.
     *
     * @throws \Carbon\Exceptions\InvalidFormatException If the format provided is invalid.
     */
    public static function createFromFormatWithCurrentLocale(
        string $format,
        string $time = null,
        \DateTimeZone|string|false|null $timezone = null
    ): static|false {
        if (is_string($time)) {
            $time = static::translateTimeString($time, static::getLocale(), 'en');
        }

        return parent::rawCreateFromFormat($format, $time, $timezone);
    }

    /**
     * Get the language portion of the locale.
     */
    public static function getLanguageFromLocale(string $locale): string
    {
        $parts = explode('_', str_replace('-', '_', $locale));

        return $parts[0];
    }
}
