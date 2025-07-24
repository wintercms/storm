<?php

namespace Winter\Storm\Tests;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class GrammarTestCase extends TestCase
{
    protected $connection = null;
    protected $grammarClass = null;
    protected $builderClass = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->connection = m::mock(Connection::class)
            ->shouldReceive('getTablePrefix')->andReturn('')
            ->shouldReceive('getServerVersion')->andReturn('3.35')
            ->shouldReceive('scalar')->andReturn('')
            ->getMock();

        $this->connection = $this->connection
            ->shouldReceive('getSchemaGrammar')->andReturn($this->getSchemaGrammar())
            ->getMock();
    }

    public function tearDown(): void
    {
        m::close();
    }

    public function getConnection()
    {
        return ($this->connection)();
    }

    protected function getBlueprint(string $table)
    {
        $blueprint =  new Blueprint($this->connection, $table);
        $this->connection->shouldReceive('getSchemaBuilder')->andReturnUsing(function () use ($blueprint) {
            return $this->getSchemaBuilder($blueprint);
        });

        return $blueprint;
    }

    protected function getSchemaGrammar()
    {
        return new ($this->grammarClass)($this->connection);
    }

    protected function getSchemaBuilder($blueprint)
    {
        return m::mock(Builder::class)
            ->shouldReceive('getColumns')->andReturn($blueprint->getColumns())
            ->shouldReceive('getForeignKeys')->andReturn([])
            ->shouldReceive('getIndexes')->andReturn([])
            ->shouldReceive('parseSchemaAndTable')->andReturn([null, $blueprint->getTable()])
            ->getMock();
    }

    protected function runBlueprint(Blueprint $blueprint)
    {
        return $blueprint->toSql();
    }
}
