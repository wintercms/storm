<?php

namespace Winter\Storm\Tests\Database\Schema\Grammars;

use Illuminate\Database\Schema\SqlServerBuilder;
use Winter\Storm\Database\Schema\Grammars\SqlServerGrammar;
use Winter\Storm\Tests\GrammarTestCase;

class SqlServerSchemaGrammarTest extends \Winter\Storm\Tests\GrammarTestCase
{
    public function setUp(): void
    {
        $this->grammarClass = SqlServerGrammar::class;
        $this->builderClass = SqlServerBuilder::class;

        parent::setUp();
    }

    public function testNoInitialModifiersAddNullable()
    {
        $initialBlueprint = $this->getBlueprint('users');
        $initialBlueprint->string('name');

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add "name" nvarchar(255) not null', $statements[0]);

        $changedBlueprint = $this->getBlueprint('users');
        $changedBlueprint->string('name')->nullable()->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $this->assertSame('alter table "users" alter column "name" nvarchar(255) null', $statements[1]);
    }

    public function testNullableInitialModifierAddDefault()
    {
        $initialBlueprint = $this->getBlueprint('users');
        $initialBlueprint->string('name')->nullable();

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add "name" nvarchar(255) null', $statements[0]);

        $changedBlueprint = $this->getBlueprint('users');
        $changedBlueprint->string('name')->default('admin')->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $this->assertSame('alter table "users" alter column "name" nvarchar(255) null', $statements[1]);
        $this->assertSame('alter table "users" add default \'admin\' for "name"', $statements[2]);
    }

    public function testNullableInitialModifierAddDefaultNotNullable()
    {
        $initialBlueprint = $this->getBlueprint('users');
        $initialBlueprint->string('name')->nullable();

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add "name" nvarchar(255) null', $statements[0]);

        $changedBlueprint = $this->getBlueprint('users');
        $changedBlueprint->string('name')->default('admin')->nullable(false)->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $this->assertSame('alter table "users" alter column "name" nvarchar(255) not null', $statements[1]);
        $this->assertSame('alter table "users" add default \'admin\' for "name"', $statements[2]);
    }
}
