<?php

// PECL HTTP constant definitions
if (!defined('HTTP_URL_REPLACE')) {
    define('HTTP_URL_REPLACE', 1);
}
if (!defined('HTTP_URL_JOIN_PATH')) {
    define('HTTP_URL_JOIN_PATH', 2);
}
if (!defined('HTTP_URL_JOIN_QUERY')) {
    define('HTTP_URL_JOIN_QUERY', 4);
}
if (!defined('HTTP_URL_STRIP_USER')) {
    define('HTTP_URL_STRIP_USER', 8);
}
if (!defined('HTTP_URL_STRIP_PASS')) {
    define('HTTP_URL_STRIP_PASS', 16);
}
if (!defined('HTTP_URL_STRIP_AUTH')) {
    define('HTTP_URL_STRIP_AUTH', 32);
}
if (!defined('HTTP_URL_STRIP_PORT')) {
    define('HTTP_URL_STRIP_PORT', 64);
}
if (!defined('HTTP_URL_STRIP_PATH')) {
    define('HTTP_URL_STRIP_PATH', 128);
}
if (!defined('HTTP_URL_STRIP_QUERY')) {
    define('HTTP_URL_STRIP_QUERY', 256);
}
if (!defined('HTTP_URL_STRIP_FRAGMENT')) {
    define('HTTP_URL_STRIP_FRAGMENT', 512);
}
if (!defined('HTTP_URL_STRIP_ALL')) {
    define('HTTP_URL_STRIP_ALL', 1024);
}

if (!function_exists('http_build_url')) {
    /**
     * Polyfill for the `http_build_url` function provided by PECL HTTP extension.
     *
     * @see \Winter\Storm\Router\UrlGenerator::buildUrl()
     * @param string|array|false|null $url The URL parts, as an array. Must match the structure returned from a `parse_url` call.
     * @param string|array|false|null $replace The URL replacement parts. Allows a developer to replace certain sections of the URL with
     *                       a different value.
     * @param int $flags A bitmask of binary or'ed HTTP_URL constants. By default, this is set to HTTP_URL_REPLACE.
     * @param array $newUrl If set, this will be filled with the array parts of the composed URL, similar to the return
     *                      value of `parse_url`.
     * @return string The generated URL as a string
     */
    function http_build_url($url, $replace = [], $flags = HTTP_URL_REPLACE, array &$newUrl = [])
    {
        return \Winter\Storm\Router\UrlGenerator::buildUrl($url, $replace, $flags, $newUrl);
    }
}

if (!function_exists('http_build_str')) {
    /**
     * Polyfill for the `http_build_str` function provided by PECL HTTP extension.
     *
     * @see \Winter\Storm\Router\UrlGenerator::buildStr()
     * @param   array   $query          Associative array of query string parameters.
     * @param   string  $prefix         Top level prefix.
     * @param   string  $arg_separator  Argument separator to use (by default the INI setting arg_separator.output will be used, or "&" if neither is set.
     * @return  string                  Returns the built query as string on success or FALSE on failure.
     */
    function http_build_str(array $query, $prefix = '', $arg_separator = null)
    {
        return \Winter\Storm\Router\UrlGenerator::buildStr($query, $prefix, $arg_separator);
    }
}
