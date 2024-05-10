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
     * Enables or disables the TTY mode.
     *
     * @throws RuntimeException In case the TTY mode is not supported or /dev/tty is not accessible.
     */
    public function setTty(bool $tty): static
    {
        if ($tty && '/' === \DIRECTORY_SEPARATOR) {
            try {
                $readable = is_readable('/dev/tty');
            } catch (\Throwable $e) {
                throw new RuntimeException($e->getMessage());
            }
        }

        return parent::setTty($tty);
    }
}
