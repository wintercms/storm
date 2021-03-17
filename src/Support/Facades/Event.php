<?php namespace Winter\Storm\Support\Facades;

use Illuminate\Support\Facades\Cache;
use Winter\Storm\Database\Model;
use Winter\Storm\Support\Testing\Fakes\EventFake;

/**
 * @see \Illuminate\Support\Facades\Event
 * @see \Winter\Storm\Support\Testing\Fakes\EventFake
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
