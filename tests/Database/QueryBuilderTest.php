<?php

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
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

    protected function getConnection($connection = null)
    {
        if ($connection) {
            return parent::getConnection($connection);
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
                'getDatabaseName'
            ])
            ->getMock();

        $connection->method('getDatabaseName')->willReturn('database');

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
