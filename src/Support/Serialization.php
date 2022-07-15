<?php namespace Winter\Storm\Support;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;

/**
 * Helper class for interacting with SerializableClosures
 */
class Serialization
{
    /**
     * Wraps a closure in a SerializableClosure, returns the provided object if it's not a closure.
     *
     * @param Closure|mixed $callable provided callable to be wrapped if it's a closure
     * @return SerializableClosure|mixed
     */
    public static function wrapClosure($callable)
    {
        if ($callable instanceof Closure) {
            $callable = new SerializableClosure($callable);
        }
        return $callable;
    }

    /**
     * If the provided argument is an instance of SerializableClosure it gets unwrapped
     * and the original closure returned, which is the recommended behaviour. Otherwise
     * the provided value is returned unmodified
     *
     * @param SerializableClosure|mixed $callable
     * @return Closure|mixed
     */
    public static function unwrapClosure($callable)
    {
        if ($callable instanceof SerializableClosure) {
            return $callable->getClosure();
        }
        return $callable;
    }
}
