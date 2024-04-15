<?php namespace Winter\Storm\Database\Traits;

use Doctrine\DBAL\Connection as DoctrineConnection;
use Doctrine\DBAL\Types\Type;
use Winter\Storm\Database\MemoryCache;
use Winter\Storm\Database\QueryBuilder;
use RuntimeException;

trait HasConnection
{
    /**
     * The instance of Doctrine connection.
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $doctrineConnection = null;

    /**
     * Type mappings that should be registered with new Doctrine connections.
     *
     * @var array<string, string>
     */
    protected $doctrineTypeMappings = [];

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

    /**
     * Is Doctrine available?
     *
     * @return bool
     */
    public function isDoctrineAvailable()
    {
        return class_exists('Doctrine\DBAL\Connection');
    }

    /**
     * Indicates whether native alter operations will be used when dropping or renaming columns, even if Doctrine DBAL is installed.
     *
     * @return bool
     */
    public function usingNativeSchemaOperations()
    {
        return ! $this->isDoctrineAvailable();
    }

    /**
     * Get a Doctrine Schema Column instance.
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Doctrine\DBAL\Schema\Column
     */
    public function getDoctrineColumn($table, $column)
    {
        $schema = $this->getDoctrineSchemaManager();

        return $schema->listTableDetails($table)->getColumn($column);
    }

    /**
     * Get the Doctrine DBAL schema manager for the connection.
     *
     * @return \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    public function getDoctrineSchemaManager()
    {
        $connection = $this->getDoctrineConnection();

        // Doctrine v2 expects one parameter while v3 expects two. 2nd will be ignored on v2...
        return $this->getDoctrineDriver()->getSchemaManager(
            $connection,
            $connection->getDatabasePlatform()
        );
    }

    /**
     * Get the Doctrine DBAL database connection instance.
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getDoctrineConnection()
    {
        if (is_null($this->doctrineConnection)) {
            $driver = $this->getDoctrineDriver();

            $this->doctrineConnection = new DoctrineConnection(array_filter([
                'pdo' => $this->getPdo(),
                'dbname' => $this->getDatabaseName(),
                'driver' => $driver->getName(),
                'serverVersion' => $this->getConfig('server_version'),
            ]), $driver);

            foreach ($this->doctrineTypeMappings as $name => $type) {
                $this->doctrineConnection
                    ->getDatabasePlatform()
                    ->registerDoctrineTypeMapping($type, $name);
            }
        }

        return $this->doctrineConnection;
    }

    /**
     * Register a custom Doctrine mapping type.
     *
     * @param  Type|class-string<Type>  $class
     * @param  string  $name
     * @param  string  $type
     * @return void
     *
     * @throws \RuntimeException
     */
    public function registerDoctrineType(Type|string $class, string $name, string $type): void
    {
        if (! $this->isDoctrineAvailable()) {
            throw new RuntimeException(
                'Registering a custom Doctrine type requires Doctrine DBAL (doctrine/dbal).'
            );
        }

        if (! Type::hasType($name)) {
            Type::getTypeRegistry()
                ->register($name, is_string($class) ? new $class() : $class);
        }

        $this->doctrineTypeMappings[$name] = $type;
    }
}
