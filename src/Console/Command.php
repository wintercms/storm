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
