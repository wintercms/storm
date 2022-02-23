<?php namespace Winter\Storm\Support\Testing\Segfault;

/**
 * Segfault Logger class
 *
 * Handles registering the Segfault logging service
 *
 * @see https://gist.github.com/lyrixx/56dfc48fb7e807dd2a229813da89a0dc#hardcore-debug-logger
 */
class Logger
{
    public static function register(string $output = 'php://stdout')
    {
        register_tick_function(function () use ($output) {

            $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
            $last = reset($bt);
            $info = sprintf("%.4f %s +%d\n", microtime(true), $last['file'], $last['line']);
            file_put_contents($output, $info, FILE_APPEND);
        });

        StreamFilter::register();
        StreamWrapper::register();
    }
}