<?php namespace Winter\Storm\Database\Traits;

use ReflectionClass;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\App;
use Winter\Storm\Database\Connectors\ConnectionFactory;
use Winter\Storm\Exception\ApplicationException;
use Winter\Storm\Filesystem\PathResolver;
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
    protected static \Illuminate\Database\Connection $arraySourceConnection;

    /**
     * Boots the ArraySource trait.
     */
    public static function bootArraySource(): void
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers())) {
            throw new ApplicationException('You must enable the SQLite PDO driver to use the ArraySource trait');
        }

        $instance = new static;

        static::arraySourceSetDbConnection(
            (!$instance->arraySourceCanStoreDb()) ? ':memory:' : $instance->arraySourceGetDbPath()
        );

        if ($instance->arraySourceDbNeedsUpdate()) {
            $instance->arraySourceCreateDb();
        }
    }

    /**
     * Gets the records stored with this model.
     *
     * This method may be overwritten to specify a custom data provider. It should always return an array of
     * associative arrays, with column names for keys and a singular value for each column.
     */
    public function getRecords(): array
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
        return static::$arraySourceConnection;
    }

    /**
     * Creates a connection to the temporary SQLite datasource.
     *
     * By default, this will create an in-memory database.
     */
    protected static function arraySourceSetDbConnection(string $database): void
    {
        $config = [
            'driver' => 'sqlite',
            'database' => $database,
        ];

        static::$arraySourceConnection = App::get(ConnectionFactory::class)->make($config);
    }

    /**
     * Creates the array source.
     *
     * This will create the temporary SQLite table and populate it with the given records.
     */
    protected function arraySourceCreateDb(): void
    {
        if ($this->arraySourceCanStoreDb()) {
            if (File::exists($this->arraySourceGetDbPath())) {
                File::delete($this->arraySourceGetDbPath());
            }
            // Create SQLite file
            File::put($this->arraySourceGetDbPath(), '');
        }

        $records = $this->getRecords();

        $this->arraySourceCreateTable();

        foreach (array_chunk($records, $this->arraySourceGetChunkSize()) as $inserts) {
            static::insert($inserts);
        }
    }

    /**
     * Creates the temporary SQLite table.
     */
    protected function arraySourceCreateTable(): void
    {
        $builder = static::resolveConnection()->getSchemaBuilder();

        try {
            $builder->create($this->getTable(), function ($table) {
                // Allow for overwriting schema types via the $recordSchema property
                $schema = ($this->propertyExists('recordSchema'))
                    ? $this->recordSchema
                    : [];
                $firstRecord = $this->getRecords()[0] ?? [];

                if (empty($schema) && empty($firstRecord)) {
                    throw new ApplicationException(
                        'A model using the ArraySource trait must either provide "$records" or "$recordSchema" as an array.'
                    );
                }

                // Add incrementing field based on the primary key if the key is not found in the first record or schema
                if (
                    $this->incrementing
                    && !array_key_exists($this->primaryKey, $schema)
                    && !array_key_exists($this->primaryKey, $firstRecord)
                ) {
                    $table->increments($this->primaryKey);
                }

                if (!empty($firstRecord)) {
                    foreach ($firstRecord as $column => $value) {
                        $type = $this->arraySourceResolveDatatype($value);

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
     * Determines the best column schema type for a given value
     *
     * @param mixed $value
     */
    protected function arraySourceResolveDatatype($value): string
    {
        if (is_int($value)) {
            return 'integer';
        }

        if (is_numeric($value)) {
            return 'float';
        }

        if (is_string($value)) {
            return 'string';
        }

        if (is_object($value) && $value instanceof \DateTimeInterface) {
            return 'dateTime';
        }

        return 'string';
    }

    /**
     * Determines if the temporary SQLite database for this model's array records will be stored.
     */
    protected function arraySourceCanStoreDb(): bool
    {
        // A model may add a $cacheArray property which defines if this model will be cached or not
        if ($this->propertyExists('cacheArray') && ((bool) $this->cacheArray) === false) {
            return false;
        }

        $sourceCacheDir = $this->arraySourceGetDbDir();

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
    protected function arraySourceGetDbDir(): string|false
    {
        $sourcePath = Config::get('database.arraySourcePath', storage_path('framework/cache/array-source/'));

        if ($sourcePath === false) {
            return false;
        }

        return PathResolver::resolve($sourcePath);
    }

    /**
     * Gets the path where the array database will be stored.
     */
    protected function arraySourceGetDbPath(): string
    {
        $class = str_replace('\\', '', static::class);
        return $this->arraySourceGetDbDir() . '/' . Str::kebab($class) . '.sqlite';
    }

    /**
     * Determines if the stored array DB should be updated.
     */
    protected function arraySourceDbNeedsUpdate(): bool
    {
        if (!$this->arraySourceCanStoreDb()) {
            return true;
        }

        if (!File::exists($this->arraySourceGetDbPath())) {
            return true;
        }

        $modelFile = (new ReflectionClass(static::class))->getFileName();

        if (File::lastModified($this->arraySourceGetDbPath()) < File::lastModified($modelFile)) {
            return true;
        }

        return false;
    }

    /**
     * Sets the array chunk size when storing inserts.
     *
     * Sometimes, SQLite will complain if given too many records to insert at once, so we will split the records up
     * into reasonable chunks and insert them in groups.
     */
    protected function arraySourceGetChunkSize(): int
    {
        return 100;
    }
}
