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
     * @return $this
     *
     * @throws RuntimeException In case the TTY mode is not supported or /dev/tty is not accessible.
     */
    public function setTty(bool $tty): static
    {
        if (($openBasedir = ini_get('open_basedir')) && !@is_readable('/dev/tty')) {
            throw new RuntimeException("\nYou have PHP open_basedir restricted for your environment.\nTTY mode has been disabled.\n");
        }

        return parent::setTty($tty);
    }
}
