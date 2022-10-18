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
        $maxLength = 80;
        $padding = 5;
        $border = 1;

        // Wrap the string to the max length of the alert box
        // taking into account the desired padding and border
        $string = wordwrap($string, $maxLength - ($border * 2) - ($padding * 2));
        $lines = explode("\n", $string);

        // Identify the length of the longest line
        $longest = 0;
        foreach ($lines as $line) {
            $length = strlen($line);
            if ($length > $longest) {
                $longest = $length;
            }
        }
        $innerLineWidth = $longest + $padding;
        $width = $innerLineWidth + ($border * 2);

        // Top border
        $this->comment(str_repeat('*', $width), $verbosity);

        // Alert content
        foreach ($lines as $line) {
            // Apply padding and borders to each line
            $this->comment(
                str_repeat('*', $border)
                . str_pad($line, $innerLineWidth, ' ', STR_PAD_BOTH)
                . str_repeat('*', $border),
                $verbosity
            );
        }

        // Bottom border
        $this->comment(str_repeat('*', $width), $verbosity);

        $this->newLine();
    }
}
