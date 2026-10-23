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

    public function testQuoteInDefaultValue()
    {
        $initialBlueprint = $this->getBlueprint('users');
        $initialBlueprint->string('name')->default("'O'Brian'");

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame("alter table \"users\" add \"name\" nvarchar(255) not null default 'O''Brian'", $statements[0]);
    }

    public function testChangeCanRemoveDefaultWithNull()
    {
        $initialBlueprint = $this->getBlueprint('users');
        $initialBlueprint->string('name')->default('admin');

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add "name" nvarchar(255) not null default \'admin\'', $statements[0]);

        $changedBlueprint = $this->getBlueprint('users');
        $changedBlueprint->string('name')->nullable()->default(null)->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $this->assertCount(2, $statements);
        $this->assertSame('alter table "users" alter column "name" nvarchar(255) null', $statements[1]);
    }

    public function testChangeKeepsADefault()
    {
        $this->existingColumns([$this->sqlServerColumn('role', 'nvarchar(510)', default: "('O''Brien')", collation: 'SQL_Latin1_General_CP1_CI_AS')]);

        $blueprint = $this->getBlueprint('users');
        $blueprint->string('role', 100)->change();

        $statements = $this->runBlueprint($blueprint);
        $this->assertSame('alter table "users" alter column "role" nvarchar(100) collate SQL_Latin1_General_CP1_CI_AS not null', $statements[1]);
        $this->assertSame('alter table "users" add default (\'O\'\'Brien\') for "role"', $statements[2]);
    }

    public function testChangeKeepsACurrentTimestampDefault()
    {
        $this->existingColumns([$this->sqlServerColumn('created_at', 'datetime', default: '(getdate())')]);

        $blueprint = $this->getBlueprint('users');
        $blueprint->timestamp('created_at')->nullable()->change();

        $statements = $this->runBlueprint($blueprint);
        $this->assertSame('alter table "users" alter column "created_at" datetime null', $statements[1]);
        $this->assertSame('alter table "users" add default (getdate()) for "created_at"', $statements[2]);
    }

    public function testChangeKeepsTheCollation()
    {
        $this->existingColumns([$this->sqlServerColumn('code', 'nvarchar(510)', collation: 'Latin1_General_BIN')]);

        $blueprint = $this->getBlueprint('users');
        $blueprint->string('code', 100)->change();

        $statements = $this->runBlueprint($blueprint);
        $this->assertCount(2, $statements);
        $this->assertSame('alter table "users" alter column "code" nvarchar(100) collate Latin1_General_BIN not null', $statements[1]);
    }

    public function testChangingTwoColumnsCompilesOneStatementPerColumn()
    {
        $this->existingColumns([$this->sqlServerColumn('first', 'nvarchar(510)'), $this->sqlServerColumn('last', 'nvarchar(510)')]);

        $blueprint = $this->getBlueprint('users');
        $blueprint->string('first', 100)->change();
        $blueprint->string('last', 100)->change();

        $statements = $this->runBlueprint($blueprint);
        $this->assertCount(4, $statements);
        $this->assertStringContainsString("[name] in ('first')", $statements[0]);
        $this->assertSame('alter table "users" alter column "first" nvarchar(100) not null', $statements[1]);
        $this->assertStringContainsString("[name] in ('last')", $statements[2]);
        $this->assertSame('alter table "users" alter column "last" nvarchar(100) not null', $statements[3]);
    }

    /**
     * A column as SQL Server's getColumns() reports it: a default as the expression of its constraint.
     */
    protected function sqlServerColumn(string $name, string $type, bool $nullable = false, ?string $default = null, ?string $collation = null): array
    {
        return compact('name', 'type', 'nullable', 'default', 'collation') + [
            'type_name' => strtok($type, '('),
            'auto_increment' => false,
            'comment' => null,
            'generation' => null,
        ];
    }
}
