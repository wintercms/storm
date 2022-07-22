<?php namespace Winter\Storm\Support\Facades;

use Illuminate\Support\Facades\Cache;
use Winter\Storm\Database\Model;
use Winter\Storm\Support\Testing\Fakes\EventFake;

/**
 * @method static \Closure createClassListener(string $listener, bool $wildcard = false)
 * @method static \Closure makeListener(\Closure|string $listener, bool $wildcard = false)
 * @method static \Illuminate\Events\Dispatcher setQueueResolver(callable $resolver)
 * @method static array getListeners(string $eventName)
 * @method static array|null dispatch(string|object $event, mixed $payload = [], bool $halt = false)
 * @method static array|null until(string|object $event, mixed $payload = [])
 * @method static bool hasListeners(string $eventName)
 * @method static void assertDispatched(string|\Closure $event, callable|int $callback = null)
 * @method static void assertDispatchedTimes(string $event, int $times = 1)
 * @method static void assertNotDispatched(string|\Closure $event, callable|int $callback = null)
 * @method static void assertNothingDispatched()
 * @method static void assertListening(string $expectedEvent, string $expectedListener)
 * @method static void flush(string $event)
 * @method static void forget(string $event)
 * @method static void forgetPushed()
 * @method static void listen(\Illuminate\Events\QueuedClosure|\Closure|string|array $events, \Illuminate\Events\QueuedClosure|\Closure|string|array $listener = null)
 * @method static void push(string $event, array $payload = [])
 * @method static void subscribe(object|string $subscriber)
 * @method static string firing()
 * @method static array|mixed|null fire(string|object $event, mixed $payload = [], bool $halt = false)
 * @method static void sortListeners(string $eventName))
 *
 * @see \Winter\Storm\Events\Dispatcher
 */
class Event extends \Illuminate\Support\Facades\Event
{
    /**
     * Replace the bound instance with a fake.
     *
     * @param  array|string  $eventsToFake
     * @return \Winter\Storm\Support\Testing\Fakes\EventFake
     */
    public static function fake($eventsToFake = [])
    {
        static::swap($fake = new EventFake(static::getFacadeRoot(), $eventsToFake));

        Model::setEventDispatcher($fake);
        Cache::refreshEventDispatcher();

        return $fake;
    }
}
