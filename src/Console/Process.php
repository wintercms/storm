<?php namespace Winter\Storm\Console;

use Symfony\Component\Process\Process as BaseProcess;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * Process class
 *
 * Fixes this symfony issue:
 *     https://github.com/symfony/symfony/issues/54874
 *
 * Not needed if the following PR gets merged:
 *     https://github.com/symfony/symfony/pull/54863
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
                // trigger exception if open_basedir restrictions prevent /dev/tty access
                $status = stat('/dev/tty');
            } catch (\Throwable $e) {
                throw new RuntimeException($e->getMessage());
            }
        }

        return parent::setTty($tty);
    }
}
