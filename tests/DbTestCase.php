<?php

use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Pivot;
use Winter\Storm\Database\Capsule\Manager as CapsuleManager;
use Winter\Storm\Database\MemoryCache;
use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Events\Dispatcher;
use Winter\Storm\Filesystem\Filesystem;

class DbTestCase extends TestCase
{
    /**
     * Database capsule.
     *
     * @var \Winter\Storm\Database\Capsule\Manager
     */
    public $db = null;

    /**
     * Migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    public $migrator = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createApplication();
        $this->createDatabase();
    }

    protected function tearDown(): void
    {
        $this->flushModelEventListeners();

        if (!is_null($this->db)) {
            unset($this->db);

            // Flush DB memory cache to prevent cached queries from affecting results
            MemoryCache::instance()->flush();
        }

        parent::tearDown();
    }

    /**
     * Create a database instance using the Capsule Manager, for testing database operations.
     *
     * @return void
     */
    protected function createDatabase(): void
    {
        if (!is_null($this->db)) {
            return;
        }

        $this->db = new CapsuleManager;
        $this->db->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => ''
        ]);

        $this->db->setAsGlobal();
        $this->db->bootEloquent();

        Model::setEventDispatcher(new Dispatcher());

        $this->app->singleton('db', function ($app) {
            return $this->db->getDatabaseManager();
        });
        $this->app['events']->listen('db.schema.getBuilder', function (\Illuminate\Database\Schema\Builder $builder) {
            $builder->blueprintResolver(function ($table, $callback) {
                return new Blueprint($table, $callback);
            });
        });
    }

    protected function runMigrations(): void
    {
        if (is_null($this->migrator)) {
            $migrationRepo = new DatabaseMigrationRepository($this->db->getDatabaseManager(), 'migrations');
            $migrationRepo->createRepository();

            $this->migrator = new Migrator($migrationRepo, $this->db->getDatabaseManager(), new Filesystem());
        }

        $this->migrator->run(dirname(__DIR__) . '/src/Database/Migrations');
    }

    /**
     * The models in Winter use a static property to store their events, these
     * will need to be targeted and reset ready for a new test cycle.
     * Pivot models are an exception since they are internally managed.
     * @return void
     */
    protected function flushModelEventListeners(): void
    {
        foreach (get_declared_classes() as $class) {
            // get_declared_classes() includes aliased classes, aliased classes are automatically lowercased
            // @https://bugs.php.net/bug.php?id=80180
            if ($class === Pivot::class || strtolower($class) === 'october\rain\database\pivot') {
                continue;
            }

            $reflectClass = new ReflectionClass($class);
            if (
                !$reflectClass->isInstantiable() ||
                !$reflectClass->isSubclassOf(Model::class) ||
                $reflectClass->isSubclassOf(Pivot::class)
            ) {
                continue;
            }

            $class::flushEventListeners();
        }

        Model::flushEventListeners();
    }
}
