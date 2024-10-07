<?php

namespace Winter\Storm\Tests\Database\Schema\Grammars;

use Illuminate\Database\Schema\Blueprint;
use Winter\Storm\Database\Schema\Grammars\SqlServerGrammar;

class SqlServerSchemaGrammarTest extends \GrammarTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->grammar = new SqlServerGrammar;
    }

    public function testNoInitialModifiersAddNullable()
    {
        $initialBlueprint = new Blueprint('users');
        $initialBlueprint->string('name');
        $this->setupConnection($initialBlueprint);

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add "name" nvarchar(255) not null', $statements[0]);

        $changedBlueprint = new Blueprint('users');
        $changedBlueprint->string('name')->nullable()->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $this->assertSame('alter table "users" alter column "name" nvarchar(255) null', $statements[1]);
    }

    public function testNullableInitialModifierAddDefault()
    {
        $initialBlueprint = new Blueprint('users');
        $initialBlueprint->string('name')->nullable();
        $this->setupConnection($initialBlueprint);

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add "name" nvarchar(255) null', $statements[0]);

        $changedBlueprint = new Blueprint('users');
        $changedBlueprint->string('name')->default('admin')->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $this->assertSame('alter table "users" alter column "name" nvarchar(255) null', $statements[1]);
        $this->assertSame('alter table "users" add default \'admin\' for "name"', $statements[2]);
    }

    public function testNullableInitialModifierAddDefaultNotNullable()
    {
        $initialBlueprint = new Blueprint('users');
        $initialBlueprint->string('name')->nullable();
        $this->setupConnection($initialBlueprint);

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add "name" nvarchar(255) null', $statements[0]);

        $changedBlueprint = new Blueprint('users');
        $changedBlueprint->string('name')->default('admin')->nullable(false)->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $this->assertSame('alter table "users" alter column "name" nvarchar(255) not null', $statements[1]);
        $this->assertSame('alter table "users" add default \'admin\' for "name"', $statements[2]);
    }
}
