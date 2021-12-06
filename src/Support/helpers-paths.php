<?php

use Winter\Storm\Filesystem\PathResolver;

if (!function_exists('config_path')) {
    /**
     * Get the path to the config folder.
     *
     * @param  string  $path
     * @return string
     */
    function config_path($path = '')
    {
        return PathResolver::join(app('path.config'), $path);
    }
}

if (!function_exists('plugins_path')) {
    /**
     * Get the path to the plugins folder.
     *
     * @param  string  $path
     * @return string
     */
    function plugins_path($path = '')
    {
        return PathResolver::join(app('path.plugins'), $path);
    }
}

if (!function_exists('uploads_path')) {
    /**
     * Get the path to the uploads folder.
     *
     * @param  string  $path
     * @return string
     */
    function uploads_path($path = '')
    {
        return PathResolver::join(Config::get('cms.storage.uploads.path', app('path.uploads')), $path);
    }
}

if (!function_exists('media_path')) {
    /**
     * Get the path to the media folder.
     *
     * @param  string  $path
     * @return string
     */
    function media_path($path = '')
    {
        return PathResolver::join(Config::get('cms.storage.media.path', app('path.media')), $path);
    }
}

if (!function_exists('themes_path')) {
    /**
     * Get the path to the themes folder.
     *
     * @param  string  $path
     * @return string
     */
    function themes_path($path = '')
    {
        return PathResolver::join(app('path.themes'), $path);
    }
}

if (!function_exists('temp_path')) {
    /**
     * Get the path to the temporary storage folder.
     *
     * @param  string  $path
     * @return string
     */
    function temp_path($path = '')
    {
        return PathResolver::join(app('path.temp'), $path);
    }
}

if (!function_exists('resolve_path')) {
    /**
     * Resolves a path to its canonical location.
     *
     * This expands all symbolic links and resolves references to /./, /../ and extra / characters in the input path
     * and returns the canonicalized absolute pathname.
     *
     * This function operates very similar to the PHP `realpath` function, except it will also work for missing files
     * and directories.
     *
     * Returns canonical path if it can be resolved, otherwise `false`.
     *
     * @param  string  $path
     * @return string|bool
     */
    function resolve_path($path)
    {
        return PathResolver::resolve($path);
    }
}
