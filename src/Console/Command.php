<?php

namespace Winter\Storm\Console;

use Illuminate\Console\Command as BaseCommand;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Termwind\Termwind;
use Winter\Storm\Extension\ExtendableTrait;
use Winter\Storm\Support\Traits\Emitter;
use function Termwind\renderUsing;

/**
 * Command base class
 * Contains utilities to make developing CLI commands nicer
 *
 * @author Luke Towers
 *
 * @method static mixed extend(callable $callback, bool $scoped = false, ?object $outerScope = null)
 */
abstract class Command extends BaseCommand implements SignalableCommandInterface
{
    use Traits\HandlesCleanup;
    use Traits\ProvidesAutocompletion;
    use ExtendableTrait;
    use Emitter;

    /**
     * @var \Winter\Storm\Foundation\Application
     */
    protected $laravel;

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

        $this->extendableConstruct();
    }

    /**
     * Override the laravel run function to allow us to run callbacks on the command prior to excution.
     * Run the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $this->laravel->make(
            OutputStyle::class, ['input' => $input, 'output' => $output]
        );

        $this->components = $this->laravel->make(Factory::class, ['output' => $this->output]);

        $this->fireEvent('beforeRun', [$this]);

        $renderer = Termwind::getRenderer();
        renderUsing($this->output->getOutput());

        try {
            // Calling the grandparent run() method, see: https://www.php.net/manual/en/language.oop5.inheritance.php#100005)
            return SymfonyCommand::run(
                $this->input = $input, $this->output
            );
        } finally {
            $this->untrap();
            // Restore the original termwind renderer
            renderUsing($renderer);
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

    /**
     * Magic allowing for extendable properties
     *
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        return $this->extendableGet($name);
    }

    /**
     * Magic allowing for extendable properties
     *
     * @param $name
     * @param $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->extendableSet($name, $value);
    }

    /**
     * Magic allowing for dynamic extension
     *
     * @param $name
     * @param $params
     * @return mixed
     */
    public function __call($name, $params)
    {
        if ($name === 'extend') {
            if (empty($params[0]) || !is_callable($params[0])) {
                throw new \InvalidArgumentException('The extend() method requires a callback parameter or closure.');
            }
            if ($params[0] instanceof \Closure) {
                return $params[0]->call($this, $params[1] ?? $this);
            }
            return \Closure::fromCallable($params[0])->call($this, $params[1] ?? $this);
        }

        return $this->extendableCall($name, $params);
    }

    /**
     * Magic allowing for dynamic static extension
     *
     * @param $name
     * @param $params
     * @return mixed|void
     */
    public static function __callStatic($name, $params)
    {
        if ($name === 'extend') {
            if (empty($params[0])) {
                throw new \InvalidArgumentException('The extend() method requires a callback parameter or closure.');
            }
            self::extendableExtendCallback($params[0], $params[1] ?? false, $params[2] ?? null);
            return;
        }

        return parent::__callStatic($name, $params);
    }
}
