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
        $this->assertSame('alter column "name" drop not null', $parts[1]);
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

    public function testQuoteInDefaultValue()
    {
        $initialBlueprint = $this->getBlueprint('users');
        $initialBlueprint->string('name')->default("'O'Brian'");

        $statements = $this->runBlueprint($initialBlueprint);
        $parts = explode(', ', $statements[0]);
        $this->assertSame("alter table \"users\" add column \"name\" varchar(255) not null default 'O''Brian'", $parts[0]);
    }

    public function testChangeCanRemoveDefaultWithNull()
    {
        $initialBlueprint = $this->getBlueprint('users');
        $initialBlueprint->string('name')->default('admin');

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame("alter table \"users\" add column \"name\" varchar(255) not null default 'admin'", $statements[0]);

        $changedBlueprint = $this->getBlueprint('users');
        $changedBlueprint->string('name')->nullable()->default(null)->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $this->assertSame('alter table "users" alter column "name" type varchar(255), alter column "name" drop not null, alter column "name" drop default, alter column "name" drop identity if exists', $statements[0]);
    }

    public function testChangeKeepsADefault()
    {
        $this->existingColumns([$this->postgresColumn('role', 'character varying(255)', default: "'O''Brien'::character varying")]);

        $blueprint = $this->getBlueprint('users');
        $blueprint->string('role', 100)->change();

        $this->assertSame(
            'alter table "users" alter column "role" type varchar(100), alter column "role" set not null, alter column "role" set default \'O\'\'Brien\'::character varying, alter column "role" drop identity if exists',
            $this->runBlueprint($blueprint)[0]
        );
    }

    public function testChangeKeepsACurrentTimestampDefault()
    {
        $this->existingColumns([$this->postgresColumn('created_at', 'timestamp(0) without time zone', default: 'CURRENT_TIMESTAMP')]);

        $blueprint = $this->getBlueprint('users');
        $blueprint->timestamp('created_at')->nullable()->change();

        $this->assertSame(
            'alter table "users" alter column "created_at" type timestamp(0) without time zone, alter column "created_at" drop not null, alter column "created_at" set default CURRENT_TIMESTAMP, alter column "created_at" drop identity if exists',
            $this->runBlueprint($blueprint)[0]
        );
    }

    public function testChangeKeepsTheCollation()
    {
        $this->existingColumns([$this->postgresColumn('code', 'character varying(255)', collation: 'C')]);

        $blueprint = $this->getBlueprint('users');
        $blueprint->string('code', 100)->change();

        $this->assertSame(
            'alter table "users" alter column "code" type varchar(100) collate "C", alter column "code" set not null, alter column "code" drop default, alter column "code" drop identity if exists',
            $this->runBlueprint($blueprint)[0]
        );
    }

    public function testChangeKeepsTheComment()
    {
        $this->existingColumns([$this->postgresColumn('note', 'character varying(255)', comment: 'a note')]);

        $blueprint = $this->getBlueprint('users');
        $blueprint->string('note', 100)->change();

        $this->assertSame('comment on column "users"."note" is \'a note\'', $this->runBlueprint($blueprint)[1]);
    }

    public function testChangingTwoColumnsCompilesOneStatementPerColumn()
    {
        $this->existingColumns([$this->postgresColumn('first', 'character varying(255)'), $this->postgresColumn('last', 'character varying(255)')]);

        $blueprint = $this->getBlueprint('users');
        $blueprint->string('first', 100)->change();
        $blueprint->string('last', 100)->change();

        $this->assertSame([
            'alter table "users" alter column "first" type varchar(100), alter column "first" set not null, alter column "first" drop default, alter column "first" drop identity if exists',
            'alter table "users" alter column "last" type varchar(100), alter column "last" set not null, alter column "last" drop default, alter column "last" drop identity if exists',
            'comment on column "users"."first" is NULL',
            'comment on column "users"."last" is NULL',
        ], $this->runBlueprint($blueprint));
    }

    /**
     * A column as PostgreSQL's getColumns() reports it: every default as an SQL expression.
     */
    protected function postgresColumn(string $name, string $type, bool $nullable = false, ?string $default = null, ?string $collation = null, ?string $comment = null): array
    {
        return compact('name', 'type', 'nullable', 'default', 'collation', 'comment') + [
            'type_name' => strtok($type, '('),
            'auto_increment' => false,
            'generation' => null,
        ];
    }
}
