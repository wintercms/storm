<?php

namespace Winter\Storm\Tests\Database\Schema\Grammars;

use Illuminate\Database\Schema\Blueprint;
use Winter\Storm\Database\Schema\Grammars\PostgresGrammar;

class PostgresSchemaGrammarTest extends \GrammarTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->grammar = new PostgresGrammar;
    }

    public function testNoInitialModifiersAddNullable()
    {
        $initialBlueprint = new Blueprint('users');
        $initialBlueprint->string('name');
        $this->setupConnection($initialBlueprint);

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add column "name" varchar(255) not null', $statements[0]);

        $changedBlueprint = new Blueprint('users');
        $changedBlueprint->string('name')->nullable()->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $parts = explode(', ', $statements[0]);
        $this->assertSame('alter table "users" alter column "name" type varchar(255)', $parts[0]);
        $this->assertSame('alter column "name" drop not null', $parts[1]);
        $this->assertSame("alter column \"name\" drop default", $parts[2]);
    }
    public function testNullableInitialModifierAddDefault()
    {
        $initialBlueprint = new Blueprint('users');
        $initialBlueprint->string('name')->nullable();
        $this->setupConnection($initialBlueprint);

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add column "name" varchar(255) null', $statements[0]);

        $changedBlueprint = new Blueprint('users');
        $changedBlueprint->string('name')->default('admin')->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $parts = explode(', ', $statements[0]);
        $this->assertSame('alter table "users" alter column "name" type varchar(255)', $parts[0]);
        $this->assertSame('alter column "name"  null', $parts[1]);
        $this->assertSame("alter column \"name\" set default 'admin'", $parts[2]);
    }

    public function testNullableInitialModifierAddDefaultNotNullable()
    {
        $initialBlueprint = new Blueprint('users');
        $initialBlueprint->string('name')->nullable();
        $this->setupConnection($initialBlueprint);

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add column "name" varchar(255) null', $statements[0]);

        $changedBlueprint = new Blueprint('users');
        $changedBlueprint->string('name')->default('admin')->nullable(false)->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $parts = explode(', ', $statements[0]);
        $this->assertSame('alter table "users" alter column "name" type varchar(255)', $parts[0]);
        $this->assertSame('alter column "name" set not null', $parts[1]);
        $this->assertSame("alter column \"name\" set default 'admin'", $parts[2]);
    }
}
