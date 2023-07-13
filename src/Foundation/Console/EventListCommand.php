<?php

namespace Winter\Storm\Foundation\Console;

use Closure;
use Illuminate\Foundation\Console\EventListCommand as BaseEventListCommand;
use Laravel\SerializableClosure\SerializableClosure;
use ReflectionFunction;
use Winter\Storm\Events\Dispatcher;

class EventListCommand extends BaseEventListCommand
{
    /**
     * Get the event / listeners from the dispatcher object.
     *
     * @return array
     */
    protected function getListenersOnDispatcher()
    {
        $events = [];

        foreach ($this->getRawListeners() as $event => $rawListeners) {
            foreach ($rawListeners as $rawListener) {
                // Winter\Storm\Events\Dispatcher->makeListener() wraps closures in a SerializableClosure object
                if ($rawListener instanceof SerializableClosure) {
                    $rawListener = $rawListener->getClosure();
                }

                // Illuminate\Events\Dispatcher->makeListener() wraps the original listener in a Closure
                if ($rawListener instanceof Closure) {
                    $reflection = new ReflectionFunction($rawListener);
                    if ($reflection->getClosureCalledClass()?->getName() === Dispatcher::class) {
                        $rawListener = $reflection->getClosureUsedVariables()['listener'] ?? $rawListener;
                    }
                }

                if (is_string($rawListener)) {
                    $events[$event][] = $this->appendListenerInterfaces($rawListener);
                } elseif ($rawListener instanceof Closure) {
                    $events[$event][] = $this->stringifyClosure($rawListener);
                } elseif (is_array($rawListener) && count($rawListener) === 2) {
                    if (is_object($rawListener[0])) {
                        $rawListener[0] = get_class($rawListener[0]);
                    }

                    $events[$event][] = $this->appendListenerInterfaces(implode('@', $rawListener));
                }
            }
        }

        return $events;
    }
}
