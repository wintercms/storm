<?php namespace Winter\Storm\Console;

use Symfony\Component\Process\Process as BaseProcess;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * Process class
 * overrides Symfony Process
 *
 * @author Marc Jauvin
 */
class Process extends BaseProcess
{
    /**
     * Enables or disables TTY mode.
     *
     * @throws RuntimeException In case the TTY mode is not supported or /dev/tty is not accessible.
     */
    public function setTty(bool $tty): static
    {
        if ($tty && '/' === \DIRECTORY_SEPARATOR) {
            try {
                // will trigger an exception if open_basedir restrictions prevent /dev/tty access
                $writable = is_writable('/dev/tty');
            } catch (\Throwable $e) {
                throw new RuntimeException($e->getMessage());
            }
        }

        return parent::setTty($tty);
    }
}
