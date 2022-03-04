<?php namespace Winter\Storm\Database\Traits;

use ReflectionClass;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\App;
use Winter\Storm\Database\Connectors\ConnectionFactory;
use Winter\Storm\Exception\ApplicationException;
use Winter\Storm\Support\Str;
use Winter\Storm\Support\Facades\File;
use Winter\Storm\Support\Facades\Config;

/**
 * Array Source trait.
 *
 * Allows a model's data to be sourced from array or collection data as opposed to a database table, allowing for
 * arbitrary models to be used for widgets or functionality that require models. This trait will create a temporary
 * SQLite table, either in cache or in memory, to house the record data and run any queries.
 *
 * Inspired by the "Sushi" library by Caleb Porzio (https://github.com/calebporzio/sushi)
 *
 * @author Ben Thomson <git@alfreido.com>
 * @author Winter CMS
 */
trait ArraySource
{
    /**
     * Connection. to the SQLite datasource.
     */
    protected static \Illuminate\Database\Connection $arraySourceConn;

    /**
     * Boots the ArraySource trait.
     */
    public static function bootArraySource(): void
    {
        $instance = new static;

        static::setArrayDbConnection(
            (!$instance->canStoreArrayDb()) ? null : $instance->getArrayDbPath()
        );

        if ($instance->arrayDbNeedsUpdate()) {
            $instance->createArrayDb();
        }
    }

    /**
     * Gets the records stored with this model.
     *
     * This method may be overwritten to specify a custom data provider. It should always return an associative array
     * with column names for keys and a singular value for each column.
     */
    public function getArrayRecords(): array
    {
        if ($this->propertyExists('records')) {
            if (!is_array($this->records)) {
                throw new ApplicationException(
                    'A model that uses the "ArraySource" trait must provide a "$records" property containing an array'
                );
            }

            return $this->records;
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public static function resolveConnection($connection = null)
    {
        return static::$arraySourceConn;
    }

    /**
     * Creates a connection to the temporary SQLite datasource.
     *
     * By default, this will create an in-memory database.
     */
    protected static function setArrayDbConnection(string $database = ':memory:'): void
    {
        $config = [
            'driver' => 'sqlite',
            'database' => $database,
        ];

        static::$arraySourceConn = App::get(ConnectionFactory::class)->make($config);
    }

    /**
     * Creates the array source.
     *
     * This will create the temporary SQLite table and populate it with the given records.
     */
    protected function createArrayDb(): void
    {
        if (File::exists($this->getArrayDbPath())) {
            File::delete($this->getArrayDbPath());
        }

        $records = $this->getArrayRecords();

        $this->createArrayDbTable();

        foreach (array_chunk($records, $this->getArrayChunkSize()) as $inserts) {
            static::insert($inserts);
        }
    }

    /**
     * Creates the temporary SQLite table.
     */
    protected function createArrayDbTable(): void
    {
        $builder = static::resolveConnection()->getSchemaBuilder();

        try {
            $builder->create($this->getTable(), function ($table) {
                // Allow for overwriting schema types via the $recordSchema property
                $schema = ($this->propertyExists('recordSchema'))
                    ? $this->recordSchema
                    : [];
                $firstRecord = $this->getArrayRecords()[0] ?? [];

                if (empty($schema) && empty($firstRecord)) {
                    throw new ApplicationException(
                        'A model using the ArraySource trait must either provide "$records" or "$recordSchema" as an array.'
                    );
                }

                // Add incrementing field based on the primary key if the key is not found in the first record or schema
                if (
                    $this->incrementing
                    && !array_key_exists($this->primaryKey, $schema)
                    && !array_key_exists($this->primaryKey, array_keys($firstRecord))
                ) {
                    $table->increments($this->primaryKey);
                }

                if (!empty($firstRecord)) {
                    foreach ($firstRecord as $column => $value) {
                        $type = $this->resolveArrayDatatype($value);

                        // Ensure the primary key is correctly created as an autoincremeting integer
                        if ($column === $this->primaryKey && $type === 'integer') {
                            $table->increments($this->primaryKey);
                            continue;
                        }

                        $type = $schema[$column] ?? $type;

                        $table->$type($column)->nullable();
                    }

                    // Create timestamp columns if they are not explicitly set in the first record
                    if (
                        $this->usesTimestamps()
                        && (
                            !in_array('created_at', array_keys($firstRecord))
                            || !in_array('updated_at', array_keys($firstRecord))
                        )
                    ) {
                        $table->timestamps();
                    }
                } else {
                    foreach ($schema as $column => $type) {
                        // Ensure the primary key is correctly created as an autoincremeting integer
                        if ($column === $this->primaryKey && $type === 'integer') {
                            $table->increments($this->primaryKey);
                            continue;
                        }

                        $table->$type($column)->nullable();
                    }

                    // Create timestamp columns if required
                    if ($this->usesTimestamps()) {
                        $table->timestamps();
                    }
                }
            });
        } catch (QueryException $e) {
            if (Str::contains($e->getMessage(), 'already exists (SQL: create table', true)) {
                // Prevents race conditions on creating the table
                return;
            }

            throw $e;
        }
    }

    /**
     * Determines if the temporary SQLite database for this model's array records will be stored.
     */
    protected function canStoreArrayDb(): bool
    {
        // A model may add a $cacheArray property which defines if this model will be cached or not
        if ($this->propertyExists('cacheArray') && ((bool) $this->cacheArray) === false) {
            return false;
        }

        $sourceCacheDir = $this->getArrayDbDir();

        if ($sourceCacheDir === false) {
            return false;
        }

        if (!File::exists($sourceCacheDir)) {
            if (!File::makeDirectory($sourceCacheDir, 0777, true)) {
                return false;
            }
        }

        return File::isWritable($sourceCacheDir);
    }

    /**
     * Gets the directory where the array databases will be stored.
     */
    protected function getArrayDbDir(): string|false
    {
        $sourcePath = Config::get('cms.arraySourcePath', storage_path('framework/cache/array-source/'));

        if ($sourcePath === false) {
            return false;
        }

        return realpath($sourcePath);
    }

    /**
     * Gets the path where the array database will be stored.
     *
     * @return string
     */
    protected function getArrayDbPath(): string
    {
        return $this->getArrayDbDir() . '/' . Str::kebab(static::class) . '.sqlite';
    }

    /**
     * Determines if the stored array DB should be updated.
     *
     * @return boolean
     */
    protected function arrayDbNeedsUpdate(): bool
    {
        if (!$this->canStoreArrayDb()) {
            return true;
        }

        if (!File::exists($this->getArrayDbPath())) {
            return true;
        }

        $modelFile = (new ReflectionClass(static::class))->getFileName();

        if (File::lastModified($this->getArrayDbPath()) < File::lastModified($modelFile)) {
            return true;
        }

        return false;
    }

    /**
     * Sets the array chunk size when storing inserts.
     *
     * Sometimes, SQLite will complain if given too many records to insert at once, so we will split the records up
     * into reasonable chunks and insert them in groups.
     *
     * @return int
     */
    protected function getArrayChunkSize()
    {
        return 50;
    }
}
