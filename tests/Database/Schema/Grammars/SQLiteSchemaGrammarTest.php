<?php

namespace Winter\Storm\Tests\Database\Schema\Grammars;

use Illuminate\Database\Schema\SQLiteBuilder;
use Winter\Storm\Database\Schema\Grammars\SQLiteGrammar;
use Winter\Storm\Tests\GrammarTestCase;

class SQLiteSchemaGrammarTest extends GrammarTestCase
{
    public function setUp(): void
    {
        $this->grammarClass = SQLiteGrammar::class;
        $this->builderClass = SQLiteBuilder::class;

        parent::setUp();
    }

    public function testNoInitialModifiersAddNullable()
    {
        $initialBlueprint = $this->getBlueprint('users');
        $initialBlueprint->string('name');

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add column "name" varchar not null', $statements[0]);

        $changedBlueprint = $this->getBlueprint('users');
        $changedBlueprint->string('name')->nullable()->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $this->assertStringContainsString('"name" varchar', $statements[0]);
    }

    public function testNullableInitialModifierAddDefault()
    {
        $initialBlueprint = $this->getBlueprint('users');
        $initialBlueprint->string('name')->nullable();

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add column "name" varchar', $statements[0]);

        $changedBlueprint = $this->getBlueprint('users');
        $changedBlueprint->string('name')->default('admin')->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $this->assertStringContainsString("varchar default 'admin'", $statements[0]);
    }

    public function testNullableInitialModifierAddDefaultNotNullable()
    {
        $initialBlueprint = $this->getBlueprint('users');
        $initialBlueprint->string('name')->nullable();

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table "users" add column "name" varchar', $statements[0]);

        $changedBlueprint = $this->getBlueprint('users');
        $changedBlueprint->string('name')->default('admin')->nullable(false)->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $this->assertStringContainsString("\"name\" varchar not null default 'admin'", $statements[0]);
    }
}
