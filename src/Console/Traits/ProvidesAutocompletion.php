<?php namespace Winter\Storm\Console\Traits;

use Winter\Storm\Support\Str;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;

/**
 * Console Command Trait that injects cross-platform signal handling to trigger
 * cleanup on exit through the handleCleanup() method on the implementing class.
 *
 * @package winter\storm
 * @author Luke Towers
 */
trait ProvidesAutocompletion
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
