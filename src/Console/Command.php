<?php namespace Winter\Storm\Console;

use Winter\Storm\Support\Str;
use Illuminate\Console\Command as BaseCommand;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;

/**
 * Command base class
 * Contains utilities to make developing CLI commands nicer
 *
 * @author Luke Towers
 */
abstract class Command extends BaseCommand
{
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
     * @return void
     */
    public function alert($string)
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
        $this->comment(str_repeat('*', $width));

        // Alert content
        foreach ($lines as $line) {
            // Apply padding and borders to each line
            $this->comment(
                str_repeat('*', $border)
                . str_pad($line, $innerLineWidth, ' ', STR_PAD_BOTH)
                . str_repeat('*', $border)
            );
        }

        // Bottom border
        $this->comment(str_repeat('*', $width));

        $this->newLine();
    }

    /**
     * Provide autocompletion for this command's input
     */
    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        $inputs = [
            'arguments' => $input->getArguments(),
            'options'   => $input->getOptions(),
        ];

        foreach ($inputs as $type => $data) {
            switch ($type) {
                case 'arguments':
                    $dataType = 'Argument';
                    $suggestionType = 'Values';
                    break;
                case 'options':
                    $dataType = 'Option';
                    $suggestionType = 'Options';
                    break;
                default:
                    // This should not be possible to ever be triggered given the type is hardcoded above
                    throw new \Exception('Invalid input type being parsed during completion');
            }
            if (!empty($data)) {
                foreach ($data as $name => $value) {
                    // Skip the command argument since that's handled by Artisan directly
                    if (
                        $type === 'arguments'
                        && in_array($name, ['command'])
                    ) {
                        continue;
                    }

                    $inputRoutingMethod = "mustSuggest{$dataType}ValuesFor";
                    $suggestionValuesMethod = Str::camel('suggest ' . $name) . $suggestionType;
                    $suggestionsMethod = 'suggest' . $suggestionType;

                    if (
                        method_exists($this, $suggestionValuesMethod)
                        && $input->{$inputRoutingMethod}($name)
                    ) {
                        $values = $this->$suggestionValuesMethod($value, $inputs);
                        $suggestions->{$suggestionsMethod}($values);
                    }
                }
            }
        }
    }

    /**
     * Example implementation of a suggestion method
     */
    // public function suggestMyArgumentValues(string $value = null, array $allInput): array
    // {
    //     if ($allInput['arguments']['dependent'] === 'matches') {
    //         return ['some', 'suggested', 'values'];
    //     }
    //     return ['all', 'values'];
    // }
}
