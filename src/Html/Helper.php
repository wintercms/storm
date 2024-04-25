<?php namespace Winter\Storm\Html;

/**
 * Methods that may be useful for processing HTML tasks
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class Helper
{
    /**
     * Converts a HTML array string to an identifier string.
     * HTML: user[location][city]
     * Result: user-location-city
     * @param string $string String to process
     * @return string
     */
    public static function nameToId($string)
    {
        return rtrim(str_replace('--', '-', str_replace(['[', ']'], '-', $string)), '-');
    }

    /**
     * Converts a HTML named array string to a PHP array. Empty values are removed.
     * HTML: user[location][city]
     * PHP:  ['user', 'location', 'city']
     * @param string $string String to process
     * @return array
     */
    public static function nameToArray($string)
    {
        $result = [$string];

        if (strpbrk($string, '[]') === false) {
            return $result;
        }

        if (preg_match('/^([^\]]+)(?:\[(.+)\])+$/', $string, $matches)) {
            if (count($matches) < 2) {
                return $result;
            }

            $result = explode('][', $matches[2]);
            array_unshift($result, $matches[1]);
        }

        $result = array_filter($result, function ($val) {
            return strlen($val) > 0;
        });

        return $result;
    }

    /**
     * Reduces the field name hierarchy depth by $level levels.
     * country[city][0][nestedform] turns into country[city][0] when reduced by 1 level;
     * country[city][0][street][0] turns into country[city][0] when reduced by 1 level;
     * country[city][0][nestedform] turns into country when reduced by 2 levels;
     * country[city][0][street][0] turns into country when reduced by 2 levels;
     * etc.
     */
    public static function reduceNameHierarchy(string $fieldName, int $level) : string
    {
        $formName = self::nameToArray($fieldName);

        if (count($formName) <= $level) {
            return "";
        }

        for ($i = 1; $i <= $level; $i++) {
            $item = array_pop($formName);
            if (is_numeric($item) && count($formName)) {
                $item = array_pop($formName);
            }
        }
        if (count($formName) < 2) {
            return array_shift($formName) ?? "";
        }

        $formNameFirst = array_shift($formName);

        return $formNameFirst . '[' . implode('][', $formName) . ']';
    }
}
