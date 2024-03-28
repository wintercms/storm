<?php

namespace Winter\Storm\Tests;

use ReflectionClass;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\BufferedOutput;
use Winter\Storm\Database\Connectors\ConnectionFactory;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Pivot;
use Winter\Storm\Events\Dispatcher;

/**
 * Base test case class for test cases involving the database.
 *
 * This class sets up an in-memory SQLite database for testing and auto-migrates applicable models.
 *
 * @author Alexey Bobkov, Samuel Georges (original version)
 * @author Winter CMS Maintainers (updated)
 * @copyright Winter CMS
 */
class DbTestCase extends TestCase
{
    /**
     * @var string[] Stores models that have been automatically migrated.
     */
    protected array $migratedModels = [];

    public function setUp(): void
    {
        parent::setUp();

        $config = [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ];
        App::make(ConnectionFactory::class)->make($config, 'testing');
        DB::setDefaultConnection('testing');
        Artisan::call('migrate', ['--database' => 'testing', '--path' => '../../../../src/Database/Migrations']);

        Model::setEventDispatcher($this->modelDispatcher());
    }

    public function tearDown(): void
    {
        $this->flushModelEventListeners();
        $this->rollbackModels();
        Artisan::call('migrate:rollback', ['--database' => 'testing', '--path' => '../../../../src/Database/Migrations']);

        parent::tearDown();
    }

    /**
     * Creates a dispatcher for the model events.
     */
    protected function modelDispatcher(): Dispatcher
    {
        $dispatcher = new Dispatcher();

        $callback = function ($eventName, $params) {
            if (!str_starts_with($eventName, 'eloquent.booted')) {
                return;
            }

            $model = $params[0];

            if (!in_array('Winter\Storm\Tests\Database\Fixtures\MigratesForTesting', class_uses_recursive($model))) {
                return;
            }

            if ($model::$migrated === false) {
                $model::migrateUp($this->getBuilder());
                $model::$migrated = true;
                $this->migratedModels[] = $model;
            }
        };

        $dispatcher->listen('*', $callback);

        return $dispatcher;
    }

    /**
     * Returns an instance of the schema builder for the test database.
     */
    protected function getBuilder(): Builder
    {
        return DB::connection()->getSchemaBuilder();
    }

    /**
     * Rolls back all migrated models.
     *
     * This should be fired in the teardown process.
     */
    protected function rollbackModels(): void
    {
        foreach ($this->migratedModels as $model) {
            $model::migrateDown($this->getBuilder());
            $model::$migrated = false;
        }

        $this->migratedModels = [];
    }

    /**
     * The models in Winter use a static property to store their events, these
     * will need to be targeted and reset ready for a new test cycle.
     * Pivot models are an exception since they are internally managed.
     * @return void
     */
    protected function flushModelEventListeners()
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
