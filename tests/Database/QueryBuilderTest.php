<?php

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Facades\DB;
use Winter\Storm\Database\MemoryCache;
use Winter\Storm\Database\Query\Grammars\MySqlGrammar;
use Winter\Storm\Database\Query\Grammars\PostgresGrammar;
use Winter\Storm\Database\Query\Grammars\SQLiteGrammar;
use Winter\Storm\Database\Query\Grammars\SqlServerGrammar;
use Winter\Storm\Database\QueryBuilder;

class QueryBuilderTest extends TestCase
{
    public function testSelectConcat()
    {
        // MySQL
        $query = $this->getMySqlBuilder()
            ->select(['id'])
            ->selectConcat(['field', ' ', 'cast'], 'full_cast')
            ->selectConcat(['field2', ' ', 'cast2'], 'full_cast2');

        $this->assertEquals(
            'select `id`, concat(`field`, \' \', `cast`) as `full_cast`, concat(`field2`, \' \', `cast2`) as `full_cast2`',
            $query->toSql()
        );

        $query = $this->getMySqlBuilder()
            ->select(['id'])
            ->selectConcat(['"field"', ' ', 'cast'], 'full_cast');

        $this->assertEquals(
            'select `id`, concat(\'field\', \' \', `cast`) as `full_cast`',
            $query->toSql()
        );

        // SQLite
        $query = $this->getSQLiteBuilder()
            ->select(['id'])
            ->selectConcat(['field', ' ', 'cast'], 'full_cast')
            ->selectConcat(['field2', ' ', 'cast2'], 'full_cast2');

        $this->assertEquals(
            'select "id", "field" || \' \' || "cast" as "full_cast", "field2" || \' \' || "cast2" as "full_cast2"',
            $query->toSql()
        );

        $query = $this->getSQLiteBuilder()
            ->select(['id'])
            ->selectConcat(['"field"', ' ', 'cast'], 'full_cast');

        $this->assertEquals(
            'select "id", \'field\' || \' \' || "cast" as "full_cast"',
            $query->toSql()
        );

        // PostgreSQL
        $query = $this->getPostgresBuilder()
            ->select(['id'])
            ->selectConcat(['field', ' ', 'cast'], 'full_cast')
            ->selectConcat(['field2', ' ', 'cast2'], 'full_cast2');

        $this->assertEquals(
            'select "id", concat("field", \' \', "cast") as "full_cast", concat("field2", \' \', "cast2") as "full_cast2"',
            $query->toSql()
        );

        $query = $this->getPostgresBuilder()
            ->select(['id'])
            ->selectConcat(['"field"', ' ', 'cast'], 'full_cast');

        $this->assertEquals(
            'select "id", concat(\'field\', \' \', "cast") as "full_cast"',
            $query->toSql()
        );

        // SQL Server
        $query = $this->getSqlServerBuilder()
            ->select(['id'])
            ->selectConcat(['field', ' ', 'cast'], 'full_cast')
            ->selectConcat(['field2', ' ', 'cast2'], 'full_cast2');

        $this->assertEquals(
            'select [id], concat([field], \' \', [cast]) as [full_cast], concat([field2], \' \', [cast2]) as [full_cast2]',
            $query->toSql()
        );

        $query = $this->getSqlServerBuilder()
            ->select(['id'])
            ->selectConcat(['"field"', ' ', 'cast'], 'full_cast');

        $this->assertEquals(
            'select [id], concat(\'field\', \' \', [cast]) as [full_cast]',
            $query->toSql()
        );
    }

    /**
     * @dataProvider duplicateCacheColumnsProvider
     */
    public function testDuplicateCachePutsQueryWithSelectedColumns(callable $run, array $columns)
    {
        $putColumns = null;

        $cache = $this->createMock(MemoryCache::class);
        $cache->method('has')->willReturn(false);
        $cache->expects($this->once())
            ->method('put')
            ->willReturnCallback(function (QueryBuilder $query) use (&$putColumns) {
                // Read during the call: get() restores the columns of the builder afterwards
                $putColumns = $query->columns;
            });

        $this->app->instance(MemoryCache::class, $cache);

        $run($this->getBuilder()->from('countries')->enableDuplicateCache());

        $this->assertSame($columns, $putColumns);
    }

    public static function duplicateCacheColumnsProvider(): array
    {
        return [
            'value()' => [fn (QueryBuilder $query) => $query->value('code'), ['code']],
            'first() with columns' => [fn (QueryBuilder $query) => $query->first(['id', 'code']), ['id', 'code']],
            'get()' => [fn (QueryBuilder $query) => $query->get(), ['*']],
        ];
    }

    public function testRememberCacheKeyIncludesSelectedColumns()
    {
        $keys = [];

        $cache = $this->createMock(Repository::class);
        $cache->expects($this->exactly(2))
            ->method('remember')
            ->willReturnCallback(function ($key) use (&$keys) {
                $keys[] = $key;

                return [];
            });

        $this->app->instance('cache', $cache);

        // DB::table() leaves the duplicate cache off, so get() calls getCached() directly
        DB::table('countries')->remember(10)->value('code');
        DB::table('countries')->remember(10)->first();

        $this->assertNotSame($keys[0], $keys[1]);
    }

    protected function getConnection($connection = null, $table = null)
    {
        if ($connection) {
            return parent::getConnection($connection, $table);
        }

        $connection = $this->getMockBuilder(ConnectionInterface::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods([
                'table',
                'raw',
                'selectOne',
                'select',
                'cursor',
                'insert',
                'update',
                'delete',
                'statement',
                'affectingStatement',
                'unprepared',
                'prepareBindings',
                'transaction',
                'beginTransaction',
                'commit',
                'rollBack',
                'transactionLevel',
                'pretend',
                'getDatabaseName',
                'getConfig',
            ])
            ->getMock();

        $connection->method('getDatabaseName')->willReturn('database');
        $connection->method('getConfig')->with('use_upsert_alias')->willReturn(false);

        return $connection;
    }

    protected function getBuilder()
    {
        $grammar = new Grammar;
        $processor = $this->createMock(Processor::class);

        return new QueryBuilder($this->getConnection(), $grammar, $processor);
    }

    protected function getMySqlBuilder()
    {
        $grammar = new MySqlGrammar;
        $processor = $this->createMock(Processor::class);

        return new QueryBuilder($this->getConnection(), $grammar, $processor);
    }

    protected function getPostgresBuilder()
    {
        $grammar = new PostgresGrammar;
        $processor = $this->createMock(Processor::class);

        return new QueryBuilder($this->getConnection(), $grammar, $processor);
    }

    protected function getSQLiteBuilder()
    {
        $grammar = new SQLiteGrammar;
        $processor = $this->createMock(Processor::class);

        return new QueryBuilder($this->getConnection(), $grammar, $processor);
    }

    protected function getSqlServerBuilder()
    {
        $grammar = new SqlServerGrammar;
        $processor = $this->createMock(Processor::class);

        return new QueryBuilder($this->getConnection(), $grammar, $processor);
    }
}
