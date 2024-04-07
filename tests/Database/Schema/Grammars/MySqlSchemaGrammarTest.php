<?php

namespace Winter\Storm\Tests\Database\Schema\Grammars;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\ForeignIdColumnDefinition;
use Winter\Storm\Database\Schema\Grammars\MySqlGrammar;
use Mockery as m;
use PDO;
use PHPUnit\Framework\TestCase;

class MySqlSchemaGrammarTest extends TestCase
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
        return $blueprint->toSql($connection, new MySqlGrammar);
    }

    public function testNoInitialModifiersAddNullable()
    {
        $initialBlueprint = new Blueprint('users');
        $initialBlueprint->string('name');

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `name` varchar(255) not null', $statements[0]);

        $changedBlueprint = new Blueprint('users');
        $changedBlueprint->string('name')->nullable()->change();

        $statements = $this->runBlueprint($initialBlueprint, $changedBlueprint);
        $this->assertCount(1, $statements);
        $this->assertSame("alter table `users` modify `name` varchar(255) null", $statements[0]);
    }

    public function testNullableInitialModifierAddDefault()
    {
        $initialBlueprint = new Blueprint('users');
        $initialBlueprint->string('name')->nullable();

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table `users` add `name` varchar(255) null', $statements[0]);

        $changedBlueprint = new Blueprint('users');
        $changedBlueprint->string('name')->default('admin')->change();

        $statements = $this->runBlueprint($initialBlueprint, $changedBlueprint);
        $this->assertSame("alter table `users` modify `name` varchar(255) null default 'admin'", $statements[0]);
    }

    public function testNullableInitialModifierAddDefaultNotNullable()
    {
        $initialBlueprint = new Blueprint('users');
        $initialBlueprint->string('name')->nullable();

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertCount(1, $statements);
        $this->assertSame('alter table `users` add `name` varchar(255) null', $statements[0]);

        $changedBlueprint = new Blueprint('users');
        $changedBlueprint->string('name')->default('admin')->nullable(false)->change();

        $statements = $this->runBlueprint($initialBlueprint, $changedBlueprint);
        $this->assertCount(1, $statements);
        $this->assertSame("alter table `users` modify `name` varchar(255) not null default 'admin'", $statements[0]);
    }
}
