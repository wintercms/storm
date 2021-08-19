<?php

use Winter\Storm\Support\Str;

if (!function_exists('str_contains')) {
    /**
     * Determine if a given string contains a given substring.
     * Polyfill for `str_contains` function provided in PHP >= 8.0
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function str_contains($haystack, $needles)
    {
        return Str::contains($haystack, $needles);
    }
}

if (!function_exists('is_countable')) {
    /**
     * Polyfill for `is_countable` method provided in PHP >= 7.3
     *
     * @param  mixed  $var
     * @return boolean
     */
    function is_countable($value)
    {
        return (is_array($value) || $value instanceof Countable);
    }
}
