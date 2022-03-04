<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Winter\Storm\Database\Connectors\ConnectionFactory;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Pivot;
use Winter\Storm\Events\Dispatcher;

class DbTestCase extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $config = [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ];
        App::make(ConnectionFactory::class)->make($config, 'testing');
        DB::setDefaultConnection('testing');

        Model::setEventDispatcher(new Dispatcher());
    }

    public function tearDown(): void
    {
        $this->flushModelEventListeners();

        parent::tearDown();
    }

    /**
     * Returns an instance of the schema builder for the test database.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function getBuilder()
    {
        return DB::connection()->getSchemaBuilder();
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
