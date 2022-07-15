<?php

use Winter\Storm\Support\Collection;

require_once("helpers-array.php");
require_once("helpers-paths.php");
require_once("helpers-str.php");

if (!function_exists('e')) {
    /**
     * Encode HTML special characters in a string.
     *
     * @param  \Illuminate\Contracts\Support\Htmlable|string  $value
     * @param  bool  $doubleEncode
     * @return string
     */
    function e($value, $doubleEncode = false)
    {
        if ($value instanceof \Illuminate\Contracts\Support\Htmlable) {
            return $value->toHtml();
        }

        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}

if (!function_exists('trans')) {
    /**
     * Translate the given message.
     *
     * @param  string|null  $id
     * @param  array   $parameters
     * @param  string|null  $locale
     * @return string
     */
    function trans($id = null, $parameters = [], $locale = null)
    {
        return app('translator')->trans($id, $parameters, $locale);
    }
}

if (!function_exists('collect')) {
    /**
     * Create a collection from the given value.
     *
     * @param  mixed  $value
     * @return \Winter\Storm\Support\Collection
     */
    function collect($value = null)
    {
        return new Collection($value);
    }
}

if (!function_exists('get')) {
    /**
     * Identical function to input(), however restricted to GET values.
     */
    function get($name = null, $default = null)
    {
        if ($name === null) {
            return Request::query();
        }

        /*
         * Array field name, eg: field[key][key2][key3]
         */
        if (class_exists('Winter\Storm\Html\Helper')) {
            $name = implode('.', Winter\Storm\Html\Helper::nameToArray($name));
        }

        return array_get(Request::query(), $name, $default);
    }
}

if (!function_exists('post')) {
    /**
     * Identical function to input(), however restricted to POST values.
     */
    function post($name = null, $default = null)
    {
        if (!in_array(Request::method(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return $default;
        }

        if ($name === null) {
            return Request::post();
        }

        /*
         * Array field name, eg: field[key][key2][key3]
         */
        if (class_exists('Winter\Storm\Html\Helper')) {
            $name = implode('.', Winter\Storm\Html\Helper::nameToArray($name));
        }

        return array_get(Request::post(), $name, $default);
    }
}

if (!function_exists('input')) {
    /**
     * Returns an input parameter or the default value.
     * Supports HTML Array names.
     * <pre>
     * $value = input('value', 'not found');
     * $name = input('contact[name]');
     * $name = input('contact[location][city]');
     * </pre>
     * Booleans are converted from strings
     * @param string|null $name
     * @param string|null $default
     * @return mixed
     */
    function input($name = null, $default = null)
    {
        if ($name === null) {
            return \Winter\Storm\Support\Facades\Input::all();
        }

        /*
         * Array field name, eg: field[key][key2][key3]
         */
        if (class_exists('Winter\Storm\Html\Helper')) {
            $name = implode('.', Winter\Storm\Html\Helper::nameToArray($name));
        }

        return \Winter\Storm\Support\Facades\Input::get($name, $default);
    }
}

if (!function_exists('trace_log')) {
    /**
     * Writes a trace message to a log file.
     * @param Exception|array|object|string... $messages
     * @return void
     */
    function trace_log(...$messages)
    {
        foreach ($messages as $message) {
            $level = 'info';

            if ($message instanceof Exception) {
                $level = 'error';
            }
            elseif (is_array($message) || is_object($message)) {
                $message = print_r($message, true);
            }

            Log::$level($message);
        }
    }
}

if (!function_exists('traceLog')) {
    /**
     * Alias for trace_log()
     * @param Exception|array|object|string... $messages
     * @return void
     */
    function traceLog(...$messages)
    {
        call_user_func_array('trace_log', $messages);
    }
}

if (!function_exists('trace_sql')) {
    /**
     * Begins to monitor all SQL output.
     * @return void
     */
    function trace_sql()
    {
        if (!defined('WINTER_NO_EVENT_LOGGING')) {
            define('WINTER_NO_EVENT_LOGGING', 1);
        }

        if (!defined('WINTER_TRACING_SQL')) {
            define('WINTER_TRACING_SQL', 1);
        }
        else {
            return;
        }

        Event::listen('illuminate.query', function ($query, $bindings, $time, $name) {
            $data = compact('bindings', 'time', 'name');

            foreach ($bindings as $i => $binding) {
                if ($binding instanceof \DateTime) {
                    $bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
                } elseif (is_string($binding)) {
                    $bindings[$i] = "'$binding'";
                }
            }

            $query = str_replace(['%', '?'], ['%%', '%s'], $query);
            $query = vsprintf($query, $bindings);

            traceLog($query);
        });
    }
}

if (!function_exists('traceSql')) {
    /**
     * Alias for trace_sql()
     * @return void
     */
    function traceSql()
    {
        trace_sql();
    }
}
