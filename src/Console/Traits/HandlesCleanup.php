<?php

namespace Winter\Storm\Console\Traits;

/**
 * Console Command Trait that injects cross-platform signal handling to trigger
 * cleanup on exit through the handleCleanup() method on the implementing class.
 *
 * >**NOTE:** This trait requires the implementing class to implement the
 * Symfony\Component\Console\Command\SignalableCommandInterface interface
 *
 * @package winter\storm
 * @author Luke Towers
 */
trait HandlesCleanup
{
    protected $windowsSignalHandlerRegistered = false;

    /**
     * Returns the process signals this command listens to
     * @see https://www.php.net/manual/en/pcntl.constants.php
     * Used to support the handleCleanup() end-class method
     */
    public function getSubscribedSignals(): array
    {
        $signals = [];

        if (method_exists($this, 'handleCleanup')) {
            // Handle Windows OS
            if (PHP_OS_FAMILY === 'Windows') {
                // Attach to Windows Ctrl+C & Ctrl+Break events
                if (function_exists('sapi_windows_set_ctrl_handler') && !$this->windowsSignalHandlerRegistered) {
                    if (sapi_windows_set_ctrl_handler([$this, 'handleWindowsSignal'], true)) {
                        $this->windowsSignalHandlerRegistered = true;
                    }
                }
            // Handle Unix-like OS
            } elseif (function_exists('pcntl_signal')) {
                $signals = [SIGINT, SIGTERM, SIGQUIT];
            }
        }

        return $signals;
    }

    /**
     * Handle the provided Unix process signal
     *
     * @ param int|false $previousExitCode
     *
     * @return int|false The exit code to return or false to continue the normal execution
     */
    public function handleSignal(int $signal, /* int|false $previousExitCode = 0 */)
    {
        // Handle the signal
        if (method_exists($this, 'handleCleanup')) {
            $this->handleCleanup();
        }

        // Exit cleanly at this point if this was a user termination
        if (function_exists('pcntl_signal') && in_array($signal, [SIGINT, SIGQUIT])) {
            return 0;
        }

        return false;
    }

    /**
     * Handle the provided Windows process singal.
     */
    public function handleWindowsSignal(int $event): void
    {
        // Remove the handler
        if ($this->windowsSignalHandlerRegistered) {
            if (sapi_windows_set_ctrl_handler([$this, 'handleWindowsSignal'], false)) {
                $this->windowsSignalHandlerRegistered = false;
            }
        }

        // Handle the signal
        if (method_exists($this, 'handleCleanup')) {
            $this->handleCleanup();

            // Exit cleanly at this point if this was a user termination
            exit(0);
        }
    }
}
