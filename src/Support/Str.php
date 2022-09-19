<?php namespace Winter\Storm\Support;

use Illuminate\Support\Str as StrHelper;

/**
 * String helper
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class Str extends StrHelper
{
    /**
     * Generates a class ID from either an object or a string of the class name.
     */
    public static function getClassId($name)
    {
        if (is_object($name)) {
            $name = get_class($name);
        }

        $name = ltrim($name, '\\');
        $name = str_replace('\\', '_', $name);

        return strtolower($name);
    }

    /**
     * Returns a class namespace
     */
    public static function getClassNamespace($name)
    {
        $name = static::normalizeClassName($name);
        return substr($name, 0, strrpos($name, "\\"));
    }

    /**
     * If $string begins with any number of consecutive symbols,
     * returns the number, otherwise returns 0
     *
     * @param string $string
     * @param string $symbol
     * @return int
     */
    public static function getPrecedingSymbols($string, $symbol)
    {
        return strlen($string) - strlen(ltrim($string, $symbol));
    }

    /**
     * Join items into a human readable list (e.g. "one, two, three, and four")
     * Uses different glue strings when there are only two elements and for
     * the final element. Defaults to joining using the Oxford comma.
     *
     * 1 item will return: $item
     * 2 items will return: $item1 . $dyadicGlue . $item2
     * 3+ items will return: $item1 . $glue . $item2 . $lastGlue . $item3
     */
    public static function join(iterable $items, string $glue = ', ', string $lastGlue = ', and ', $dyadicGlue = ' and '): string
    {
        $result = '';
        $i = 0;
        $total = count($items);
        foreach ($items as $item) {
            $i++;

            // Only add glue if we're not on the first item
            if ($i !== 1) {
                // Add diadic glue between the first and last item
                if ($i === 2 && $total === 2) {
                    $result .= $dyadicGlue;

                // Add the last glue if we're on the last item
                } elseif ($i === $total) {
                    $result .= $lastGlue;

                // Add the normal glue otherwise
                } else {
                    $result .= $glue;
                }
            }

            $result .= $item;
        }

        return $result;
    }

    /**
     * Apply an index to a string, i.e.
     * winter -> winter_1
     * winter_1 -> winter_2
     */
    public static function index(string $str, string $separator = '_', int $starting = 1, int $step = 1): string
    {
        if (!preg_match('/(.*?)' . $separator . '(\d*$)/', $str, $matches)) {
            return $str . $separator . $starting;
        }

        return $matches[1] . $separator . (((int) $matches[2]) + $step);
    }

    /**
     * Apply a unique index to a string from provided list i.e.
     * winter, [winter_1, winter_2] -> winter_3
     * winter, [winter_1, winter_3] -> winter_4
     */
    public static function unique(string $str, array $list, string $separator = '_', int $starting = 1, int $step = 1): string
    {
        $indexes = [];

        foreach ($list as $item) {
            if (!preg_match('/(.*?)' . $str . $separator . '(\d*$)/', $item, $matches)) {
                continue;
            }

            $indexes[] = (int) $matches[2];
        }

        return empty($indexes)
            ? $str . $separator . $starting
            : $str . $separator . (max($indexes) + $step);
    }

    /**
     * Converts line breaks to a standard \r\n pattern.
     */
    public static function normalizeEol($string)
    {
        return preg_replace('~\R~u', "\r\n", $string);
    }

    /**
     * Removes the starting slash from a class namespace \
     */
    public static function normalizeClassName($name)
    {
        if (is_object($name)) {
            $name = get_class($name);
        }

        $name = '\\'.ltrim($name, '\\');
        return $name;
    }

    /**
     * Converts number to its ordinal English form.
     *
     * This method converts 13 to 13th, 2 to 2nd ...
     *
     * @param integer $number Number to get its ordinal value
     * @return string Ordinal representation of given string.
     */
    public static function ordinal($number)
    {
        if (in_array($number % 100, range(11, 13))) {
            return $number.'th';
        }

        switch ($number % 10) {
            case 1:
                return $number.'st';
            case 2:
                return $number.'nd';
            case 3:
                return $number.'rd';
            default:
                return $number.'th';
        }
    }
}
