<?php

namespace Tests\Database\Schema\Grammars;

use Illuminate\Database\Schema\MySqlBuilder;
use Winter\Storm\Database\Schema\Grammars\MySqlGrammar;
use Winter\Storm\Tests\GrammarTestCase;

class MySqlSchemaGrammarTest extends \Winter\Storm\Tests\GrammarTestCase
{
    public function setUp(): void
    {
        $this->grammarClass = MySqlGrammar::class;
        $this->builderClass = MySqlBuilder::class;

        parent::setUp();
    }

    public function testNoInitialModifiersAddNullable()
    {

        $initialBlueprint = $this->getBlueprint('users');
        $initialBlueprint->string('name');

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table `users` add `name` varchar(255) not null', $statements[0]);

        $changedBlueprint = $this->getBlueprint('users');
        $changedBlueprint->string('name')->nullable()->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $this->assertSame("alter table `users` modify `name` varchar(255) null", $statements[0]);
    }

    public function testNullableInitialModifierAddDefault()
    {
        $initialBlueprint = $this->getBlueprint('users');
        $initialBlueprint->string('name')->nullable();

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table `users` add `name` varchar(255) null', $statements[0]);

        $changedBlueprint = $this->getBlueprint('users');
        $changedBlueprint->string('name')->default('admin')->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $this->assertSame("alter table `users` modify `name` varchar(255) null default 'admin'", $statements[0]);
    }

    public function testNullableInitialModifierAddDefaultNotNullable()
    {
        $initialBlueprint = $this->getBlueprint('users');
        $initialBlueprint->string('name')->nullable();

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table `users` add `name` varchar(255) null', $statements[0]);

        $changedBlueprint = $this->getBlueprint('users');
        $changedBlueprint->string('name')->default('admin')->nullable(false)->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $this->assertSame("alter table `users` modify `name` varchar(255) not null default 'admin'", $statements[0]);
    }
}
