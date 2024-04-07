<?php

namespace Winter\Storm\Tests\Database\Schema\Grammars;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\ForeignIdColumnDefinition;
use Winter\Storm\Database\Schema\Grammars\PostgresGrammar;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class PostgresSchemaGrammarTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    protected function setupConnection(Blueprint $blueprint)
    {
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getSchemaBuilder')->andReturn($this->getSchemaBuilder($blueprint));
        return $connection;
    }

    protected function getSchemaBuilder(Blueprint $blueprint)
    {
        $schemaBuilder = m::mock(Builder::class);
        $schemaBuilder->shouldReceive('getColumns')->andReturn($blueprint->getColumns());

        return $schemaBuilder;
    }

    protected function runBlueprint(Blueprint $initialBlueprint, Blueprint $blueprint = null)
    {
        $connection = $this->setupConnection($initialBlueprint);
        if (is_null($blueprint)) {
            $blueprint = $initialBlueprint;
        }
        return $blueprint->toSql($connection, new PostgresGrammar);
    }

    public function testNoInitialModifiersAddNullable()
    {
        $initialBlueprint = new Blueprint('users');
        $initialBlueprint->string('name');

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add column "name" varchar(255) not null', $statements[0]);

        $changedBlueprint = new Blueprint('users');
        $changedBlueprint->string('name')->nullable()->change();

        $statements = $this->runBlueprint($initialBlueprint, $changedBlueprint);
        $parts = explode(', ', $statements[0]);
        $this->assertSame('alter table "users" alter column "name" type varchar(255)', $parts[0]);
        $this->assertSame('alter column "name" drop not null', $parts[1]);
        $this->assertSame("alter column \"name\" drop default", $parts[2]);
    }
    public function testNullableInitialModifierAddDefault()
    {
        $initialBlueprint = new Blueprint('users');
        $initialBlueprint->string('name')->nullable();

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add column "name" varchar(255) null', $statements[0]);

        $changedBlueprint = new Blueprint('users');
        $changedBlueprint->string('name')->default('admin')->change();

        $statements = $this->runBlueprint($initialBlueprint, $changedBlueprint);
        $parts = explode(', ', $statements[0]);
        $this->assertSame('alter table "users" alter column "name" type varchar(255)', $parts[0]);
        $this->assertSame('alter column "name"  null', $parts[1]);
        $this->assertSame("alter column \"name\" set default 'admin'", $parts[2]);
    }

    public function testNullableInitialModifierAddDefaultNotNullable()
    {
        $initialBlueprint = new Blueprint('users');
        $initialBlueprint->string('name')->nullable();

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add column "name" varchar(255) null', $statements[0]);

        $changedBlueprint = new Blueprint('users');
        $changedBlueprint->string('name')->default('admin')->nullable(false)->change();

        $statements = $this->runBlueprint($initialBlueprint, $changedBlueprint);
        $parts = explode(', ', $statements[0]);
        $this->assertSame('alter table "users" alter column "name" type varchar(255)', $parts[0]);
        $this->assertSame('alter column "name" set not null', $parts[1]);
        $this->assertSame("alter column \"name\" set default 'admin'", $parts[2]);
    }
}
