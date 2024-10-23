<?php

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Grammars\Grammar;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class GrammarTestCase extends TestCase
{
    protected Connection $connection;
    protected Grammar $grammar;

    protected function tearDown(): void
    {
        m::close();
    }

    protected function setupConnection(Blueprint $blueprint)
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getServerVersion')->andReturn('3.35');
        $connection->shouldReceive('getSchemaBuilder')->andReturn($this->getSchemaBuilder($blueprint));
        $connection->shouldReceive('scalar')->andReturn('');
        $this->connection = $connection;
    }

    protected function getSchemaBuilder(Blueprint $blueprint)
    {
        $schemaBuilder = m::mock(Builder::class);
        $schemaBuilder->shouldReceive('getColumns')->andReturn($blueprint->getColumns());
        $schemaBuilder->shouldReceive('getForeignKeys')->andReturn([]);
        $schemaBuilder->shouldReceive('getIndexes')->andReturn([]);

        return $schemaBuilder;
    }

    protected function runBlueprint(Blueprint $blueprint)
    {
        return $blueprint->toSql($this->connection, $this->grammar);
    }
}
