<?php namespace Winter\Storm\Support\Traits;

use Closure;
use Illuminate\Events\QueuedClosure;
use Illuminate\Support\Traits\ReflectsClosures;
use Winter\Storm\Support\Arr;
use Winter\Storm\Support\Serialization;

/**
 * Adds event related features to any class.
 *
 * @author Alexey Bobkov, Samuel Georges
 */
trait Emitter
{
    use ReflectsClosures;

    /**
     * @var array Collection of registered events to be fired once only.
     */
    protected $emitterSingleEventCollection = [];

    /**
     * @var array Collection of registered events.
     */
    protected $emitterEventCollection = [];

    /**
     * @var array Sorted collection of events.
     */
    protected $emitterEventSorted = [];

    /**
     * Create a new event binding.
     * @param string|Closure|QueuedClosure  $event
     * @param mixed  $callback when the third parameter is omitted and a Closure or QueuedClosure is provided
     * this parameter is used as an integer this is used as priority variable
     * @param int $priority
     * @return self
     */
    public function bindEvent($event, $callback = null, $priority = 0)
    {
        if ($event instanceof Closure || $event instanceof QueuedClosure) {
            if ($priority === 0 && (is_int($callback) || filter_var($callback, FILTER_VALIDATE_INT))) {
                $priority = (int) $callback;
            }
        }
        if ($event instanceof Closure) {
            return $this->bindEvent($this->firstClosureParameterType($event), $event, $priority);
        } elseif ($event instanceof QueuedClosure) {
            return $this->bindEvent($this->firstClosureParameterType($event->closure), $event->resolve(), $priority);
        } elseif ($callback instanceof QueuedClosure) {
            $callback = $callback->resolve();
        }
        $this->emitterEventCollection[$event][$priority][] = Serialization::wrapClosure($callback);
        unset($this->emitterEventSorted[$event]);
        return $this;
    }

    /**
     * Create a new event binding that fires once only
     * @param string|Closure|QueuedClosure  $event
     * @param QueuedClosure|Closure|null  $callback When a Closure or QueuedClosure is provided as the first parameter
     * this parameter can be omitted
     * @return self
     */
    public function bindEventOnce($event, $callback = null)
    {
        if ($event instanceof Closure) {
            return $this->bindEventOnce($this->firstClosureParameterType($event), $event);
        } elseif ($event instanceof QueuedClosure) {
            return $this->bindEventOnce($this->firstClosureParameterType($event->closure), $event->resolve());
        } elseif ($callback instanceof QueuedClosure) {
            $callback = $callback->resolve();
        }
        $this->emitterSingleEventCollection[$event][] = Serialization::wrapClosure($callback);
        return $this;
    }

    /**
     * Sort the listeners for a given event by priority.
     *
     * @param  string  $eventName
     * @return void
     */
    protected function emitterEventSortEvents($eventName)
    {
        $this->emitterEventSorted[$eventName] = [];

        if (isset($this->emitterEventCollection[$eventName])) {
            krsort($this->emitterEventCollection[$eventName]);

            $this->emitterEventSorted[$eventName] = call_user_func_array('array_merge', $this->emitterEventCollection[$eventName]);
        }
    }

    /**
     * Destroys an event binding.
     * @param string|array|object $event Event to destroy
     * @return self
     */
    public function unbindEvent($event = null)
    {
        /*
         * Multiple events
         */
        if (is_array($event)) {
            foreach ($event as $_event) {
                $this->unbindEvent($_event);
            }
            return $this;
        }

        if (is_object($event)) {
            $event = get_class($event);
        }

        if ($event === null) {
            unset($this->emitterSingleEventCollection, $this->emitterEventCollection, $this->emitterEventSorted);
            return $this;
        }

        if (isset($this->emitterSingleEventCollection[$event])) {
            unset($this->emitterSingleEventCollection[$event]);
        }

        if (isset($this->emitterEventCollection[$event])) {
            unset($this->emitterEventCollection[$event]);
        }

        if (isset($this->emitterEventSorted[$event])) {
            unset($this->emitterEventSorted[$event]);
        }

        return $this;
    }

    /**
     * Fire an event and call the listeners.
     * @param string $event Event name
     * @param array $params Event parameters
     * @param boolean $halt Halt after first non-null result
     * @return array|mixed|null If halted, the first non-null result. If not halted, an array of event results. Returns
     *  null if no listeners returned a result.
     */
    public function fireEvent($event, $params = [], $halt = false)
    {
        // When the given "event" is actually an object we will assume it is an event
        // object and use the class as the event name and this event itself as the
        // payload to the handler, which makes object based events quite simple.
        list($event, $params) = $this->parseEventAndPayload($event, $params);

        $result = [];

        /*
         * Single events
         */
        if (isset($this->emitterSingleEventCollection[$event])) {
            foreach ($this->emitterSingleEventCollection[$event] as $callback) {
                $response = call_user_func_array(Serialization::unwrapClosure($callback), $params);
                if (is_null($response)) {
                    continue;
                }
                if ($halt) {
                    return $response;
                }
                $result[] = $response;
            }

            unset($this->emitterSingleEventCollection[$event]);
        }

        /*
         * Recurring events, with priority
         */
        if (isset($this->emitterEventCollection[$event])) {
            if (!isset($this->emitterEventSorted[$event])) {
                $this->emitterEventSortEvents($event);
            }

            foreach ($this->emitterEventSorted[$event] as $callback) {
                $response = call_user_func_array(Serialization::unwrapClosure($callback), $params);
                if (is_null($response)) {
                    continue;
                }
                if ($halt) {
                    return $response;
                }
                $result[] = $response;
            }
        }

        return $halt ? null : $result;
    }

    /**
     * Parse the given event and payload and prepare them for dispatching.
     *
     * @param  mixed  $event
     * @param  mixed  $payload
     * @return array
     */
    protected function parseEventAndPayload($event, $payload = null)
    {
        if (is_object($event)) {
            [$payload, $event] = [[$event], get_class($event)];
        }

        return [$event, Arr::wrap($payload)];
    }
}
