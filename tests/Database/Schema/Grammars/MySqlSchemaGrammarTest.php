<?php

namespace Tests\Database\Schema\Grammars;

use Illuminate\Database\Schema\Blueprint;
use Winter\Storm\Database\Schema\Grammars\MySqlGrammar;

class MySqlSchemaGrammarTest extends \GrammarTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->grammar = new MySqlGrammar;
    }

    public function testNoInitialModifiersAddNullable()
    {
        $initialBlueprint = new Blueprint('users');
        $initialBlueprint->string('name');
        $this->setupConnection($initialBlueprint);

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table `users` add `name` varchar(255) not null', $statements[0]);

        $changedBlueprint = new Blueprint('users');
        $changedBlueprint->string('name')->nullable()->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $this->assertSame("alter table `users` modify `name` varchar(255) null", $statements[0]);
    }

    public function testNullableInitialModifierAddDefault()
    {
        $initialBlueprint = new Blueprint('users');
        $initialBlueprint->string('name')->nullable();
        $this->setupConnection($initialBlueprint);

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table `users` add `name` varchar(255) null', $statements[0]);

        $changedBlueprint = new Blueprint('users');
        $changedBlueprint->string('name')->default('admin')->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $this->assertSame("alter table `users` modify `name` varchar(255) null default 'admin'", $statements[0]);
    }

    public function testNullableInitialModifierAddDefaultNotNullable()
    {
        $initialBlueprint = new Blueprint('users');
        $initialBlueprint->string('name')->nullable();
        $this->setupConnection($initialBlueprint);

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame('alter table `users` add `name` varchar(255) null', $statements[0]);

        $changedBlueprint = new Blueprint('users');
        $changedBlueprint->string('name')->default('admin')->nullable(false)->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $this->assertSame("alter table `users` modify `name` varchar(255) not null default 'admin'", $statements[0]);
    }
}
