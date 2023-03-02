<?php namespace Winter\Storm\Database\Connections;

use Winter\Storm\Database\MemoryCache;
use Winter\Storm\Database\QueryBuilder;
use Illuminate\Database\Connection as ConnectionBase;

class Connection extends ConnectionBase
{
    /**
     * Get a new query builder instance.
     *
     * @return \Winter\Storm\Database\QueryBuilder
     */
    public function query()
    {
        return new QueryBuilder(
            $this,
            $this->getQueryGrammar(),
            $this->getPostProcessor()
        );
    }

    /**
     * Flush the memory cache.
     * @return void
     */
    public static function flushDuplicateCache()
    {
        MemoryCache::instance()->flush();
    }

    /**
     * Log a query in the connection's query log.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @param  float|null  $time
     * @return void
     */
    public function logQuery($query, $bindings, $time = null)
    {
        $this->fireEvent('illuminate.query', [$query, $bindings, $time, $this->getName()]);

        parent::logQuery($query, $bindings, $time);
    }

    /**
     * Fire an event for this connection.
     *
     * @param  string  $event
     * @return array|null
     */
    protected function fireConnectionEvent($event)
    {
        $this->fireEvent('connection.'.$this->getName().'.'.$event, $this);

        parent::fireConnectionEvent($event);
    }

    /**
     * Fire the given event if possible.
     */
    protected function fireEvent(string $event, array|object $attributes = []): void
    {
        /** @var \Winter\Storm\Events\Dispatcher|null */
        $eventManager = $this->events;

        if (!isset($eventManager)) {
            return;
        }

        $eventManager->dispatch($event, $attributes);
    }
}
