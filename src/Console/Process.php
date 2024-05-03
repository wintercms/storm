<?php namespace Winter\Storm\Console;

use Symfony\Component\Process\Process as BaseProcess;
use Winter\Storm\Exception\ApplicationException;

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
     * @throws RuntimeException In case the TTY mode is not supported
     * @throws ApplicationException In case /dev/tty is not accessible
     */
    public function setTty(bool $tty): static
    {
        if (($openBasedir = ini_get('open_basedir')) && !@is_readable('/dev/tty')) {
            throw new ApplicationException("\nYou have PHP open_basedir restricted for your environment.\nTTY mode has been disabled.\n");
        }

        return parent::setTty($tty);
    }
}
