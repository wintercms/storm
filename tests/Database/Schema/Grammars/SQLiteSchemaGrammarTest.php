<?php

namespace Winter\Storm\Tests\Database\Schema\Grammars;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ForeignIdColumnDefinition;
use Winter\Storm\Database\Schema\Grammars\SQLiteGrammar;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SQLiteSchemaGrammarTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    protected function setupConnection(Blueprint $blueprint)
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getSchemaBuilder')->andReturn($this->getSchemaBuilder($blueprint));
        $connection->shouldReceive('scalar')->andReturn('');
        return $connection;
    }

    protected function getSchemaBuilder(Blueprint $blueprint)
    {
        $schemaBuilder = m::mock(Builder::class);
        $schemaBuilder->shouldReceive('getColumns')->andReturn($blueprint->getColumns());
        $schemaBuilder->shouldReceive('getForeignKeys')->andReturn([]);
        $schemaBuilder->shouldReceive('getIndexes')->andReturn([]);

        return $schemaBuilder;
    }

    protected function runBlueprint(Blueprint $initialBlueprint, Blueprint $blueprint = null)
    {
        $connection = $this->setupConnection($initialBlueprint);
        if (is_null($blueprint)) {
            $blueprint = $initialBlueprint;
        }
        return $blueprint->toSql($connection, new SQLiteGrammar);
    }

    public function testNoInitialModifiersAddNullable()
    {
        $initialBlueprint = new Blueprint('users');
        $initialBlueprint->string('name');

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add column "name" varchar not null', $statements[0]);

        $changedBlueprint = new Blueprint('users');
        $changedBlueprint->string('name')->nullable()->change();

        $statements = $this->runBlueprint($initialBlueprint, $changedBlueprint);
        $this->assertStringContainsString('"name" varchar', $statements[0]);
    }

    public function testNullableInitialModifierAddDefault()
    {
        $initialBlueprint = new Blueprint('users');
        $initialBlueprint->string('name')->nullable();

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add column "name" varchar', $statements[0]);

        $changedBlueprint = new Blueprint('users');
        $changedBlueprint->string('name')->default('admin')->change();

        $statements = $this->runBlueprint($initialBlueprint, $changedBlueprint);
        $this->assertStringContainsString("varchar default 'admin'", $statements[0]);
    }

    public function testNullableInitialModifierAddDefaultNotNullable()
    {
        $initialBlueprint = new Blueprint('users');
        $initialBlueprint->string('name')->nullable();

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add column "name" varchar', $statements[0]);

        $changedBlueprint = new Blueprint('users');
        $changedBlueprint->string('name')->default('admin')->nullable(false)->change();

        $statements = $this->runBlueprint($initialBlueprint, $changedBlueprint);
        $this->assertStringContainsString("\"name\" varchar not null default 'admin'", $statements[0]);
    }
}
