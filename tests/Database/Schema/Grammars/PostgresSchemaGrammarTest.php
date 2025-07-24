<?php

namespace Winter\Storm\Tests\Database\Schema\Grammars;

use Illuminate\Database\Schema\PostgresBuilder;
use Winter\Storm\Database\Schema\Grammars\PostgresGrammar;
use Winter\Storm\Tests\GrammarTestCase;

class PostgresSchemaGrammarTest extends \Winter\Storm\Tests\GrammarTestCase
{
    public function setUp(): void
    {
        $this->grammarClass = PostgresGrammar::class;
        $this->builderClass = PostgresBuilder::class;

        parent::setUp();
    }

    public function testNoInitialModifiersAddNullable()
    {
        $initialBlueprint = $this->getBlueprint('users');
        $initialBlueprint->string('name');

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add column "name" varchar(255) not null', $statements[0]);

        $changedBlueprint = $this->getBlueprint('users');
        $changedBlueprint->string('name')->nullable()->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $parts = explode(', ', $statements[0]);
        $this->assertSame('alter table "users" alter column "name" type varchar(255)', $parts[0]);
        $this->assertSame('alter column "name" drop not null', $parts[1]);
        $this->assertSame("alter column \"name\" drop default", $parts[2]);
    }

    public function testNullableInitialModifierAddDefault()
    {
        $initialBlueprint = $this->getBlueprint('users');
        $initialBlueprint->string('name')->nullable();

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add column "name" varchar(255) null', $statements[0]);

        $changedBlueprint = $this->getBlueprint('users');
        $changedBlueprint->string('name')->default('admin')->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $parts = explode(', ', $statements[0]);
        $this->assertSame('alter table "users" alter column "name" type varchar(255)', $parts[0]);
        $this->assertSame('alter column "name"  null', $parts[1]);
        $this->assertSame("alter column \"name\" set default 'admin'", $parts[2]);
    }

    public function testNullableInitialModifierAddDefaultNotNullable()
    {
        $initialBlueprint = $this->getBlueprint('users');
        $initialBlueprint->string('name')->nullable();

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add column "name" varchar(255) null', $statements[0]);

        $changedBlueprint = $this->getBlueprint('users');
        $changedBlueprint->string('name')->default('admin')->nullable(false)->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $parts = explode(', ', $statements[0]);
        $this->assertSame('alter table "users" alter column "name" type varchar(255)', $parts[0]);
        $this->assertSame('alter column "name" set not null', $parts[1]);
        $this->assertSame("alter column \"name\" set default 'admin'", $parts[2]);
    }
}
