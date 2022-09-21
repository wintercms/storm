<?php namespace Winter\Storm\Support;

use Illuminate\Support\Arr as ArrHelper;
use InvalidArgumentException;

/**
 * Array helper
 *
 * @author Winter CMS
 */
class Arr extends ArrHelper
{
    /**
     * Build a new array using a callback.
     */
    public static function build(array $array, callable $callback): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            list($innerKey, $innerValue) = call_user_func($callback, $key, $value);

            $results[$innerKey] = $innerValue;
        }

        return $results;
    }

    /**
     * Moves the key to the index within the array, negative index will work backwards from the end of the array
     * @throws InvalidArgumentException if the key does not exist in the array
     */
    public static function moveKeyToIndex(array $array, string|int $targetKey, int $index): array
    {
        if (!array_key_exists($targetKey, $array)) {
            throw new InvalidArgumentException(sprintf('Key "%s" does not exist in the array', $targetKey));
        }

        $keys = array_diff(array_keys($array), [$targetKey]);

        array_splice($keys, $index, 0, [$targetKey]);

        $sorted = [];
        foreach ($keys as $key) {
            $sorted[$key] = $array[$key];
        }

        return $sorted;
    }
}
