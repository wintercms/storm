<?php namespace Winter\Storm\Console;

use Illuminate\Console\Command as BaseCommand;
use Symfony\Component\Console\Command\SignalableCommandInterface;

/**
 * Command base class
 * Contains utilities to make developing CLI commands nicer
 *
 * @author Luke Towers
 */
abstract class Command extends BaseCommand implements SignalableCommandInterface
{
    use Traits\HandlesCleanup;
    use Traits\ProvidesAutocompletion;

    /**
     * @var array List of commands that this command replaces (aliases)
     */
    protected $replaces = [];

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        if (!empty($this->replaces)) {
            $this->setAliases($this->replaces);
        }
    }

    /**
     * Write a string in an alert box.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function alert($string, $verbosity = null)
    {
        $this->components->alert($string, $this->parseVerbosity($verbosity));
    }

    /**
     * Write a string as error output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function error($string, $verbosity = null)
    {
        $this->components->error($string, $this->parseVerbosity($verbosity));
    }
}
