<?php namespace Winter\Storm\Support;


use Opis\Closure\SerializableClosure;
use Closure;

class Serialisation
{
    /**
     * Wraps a closure in a SerializableClosure, returns the provided object if it's not a closure.
     * @param Closure|mixed $callable provided callable to be wrapped if it's a closure
     * @return mixed|SerializableClosure
     */
    public static function wrapClosure($callable)
    {
        if ($callable instanceof Closure && !($callable instanceof SerializableClosure)) {
            $callable = new SerializableClosure($callable);
        }
        return $callable;
    }

    /**
     * if the provided argument is an instance of SerializableClosure it gets unwrapped
     * and the original closure returned, which is the recommended behaviour.
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
