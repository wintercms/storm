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

    public function testQuoteInDefaultValue()
    {
        $initialBlueprint = $this->getBlueprint('users');
        $initialBlueprint->string('name')->default("'O'Brian'");

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame("alter table `users` add `name` varchar(255) not null default 'O''Brian'", $statements[0]);
    }

    public function testChangeCanRemoveDefaultWithNull()
    {
        $initialBlueprint = $this->getBlueprint('users');
        $initialBlueprint->string('name')->default('admin');

        $statements = $this->runBlueprint($initialBlueprint);
        $this->assertSame("alter table `users` add `name` varchar(255) not null default 'admin'", $statements[0]);

        $changedBlueprint = $this->getBlueprint('users');
        $changedBlueprint->string('name')->nullable()->default(null)->change();

        $statements = $this->runBlueprint($changedBlueprint);
        $this->assertSame('alter table `users` modify `name` varchar(255) null', $statements[0]);
    }

    public function testChangeKeepsTheCollation()
    {
        $this->existingColumns([$this->mysqlColumn('name', 'varchar(255)', collation: 'utf8mb4_bin')]);

        $blueprint = $this->getBlueprint('users');
        $blueprint->string('name', 100)->change();

        $this->assertSame("alter table `users` modify `name` varchar(100) collate 'utf8mb4_bin' not null", $this->runBlueprint($blueprint)[0]);
    }

    public function testChangeToANonTextTypeDropsTheCollation()
    {
        $this->existingColumns([$this->mysqlColumn('code', 'varchar(255)', collation: 'utf8mb4_bin')]);

        $blueprint = $this->getBlueprint('users');
        $blueprint->integer('code')->change();

        $this->assertSame('alter table `users` modify `code` int not null', $this->runBlueprint($blueprint)[0]);
    }

    public function testChangeKeepsACurrentTimestampDefault()
    {
        $this->existingColumns([$this->mysqlColumn('created_at', 'timestamp', default: 'CURRENT_TIMESTAMP')]);

        $blueprint = $this->getBlueprint('users');
        $blueprint->timestamp('created_at')->nullable()->change();

        $this->assertSame('alter table `users` modify `created_at` timestamp null default CURRENT_TIMESTAMP', $this->runBlueprint($blueprint)[0]);
    }

    public function testChangingTwoColumnsCompilesOneStatementPerColumn()
    {
        $this->existingColumns([$this->mysqlColumn('first', 'varchar(255)'), $this->mysqlColumn('last', 'varchar(255)')]);

        $blueprint = $this->getBlueprint('users');
        $blueprint->string('first', 100)->change();
        $blueprint->string('last', 100)->change();

        $this->assertSame([
            'alter table `users` modify `first` varchar(100) not null',
            'alter table `users` modify `last` varchar(100) not null',
        ], $this->runBlueprint($blueprint));
    }

    /**
     * A column as MySQL's getColumns() reports it: literal defaults unquoted, expressions as is.
     */
    protected function mysqlColumn(string $name, string $type, bool $nullable = false, ?string $default = null, ?string $collation = null): array
    {
        return compact('name', 'type', 'nullable', 'default', 'collation') + [
            'type_name' => strtok($type, '('),
            'auto_increment' => false,
            'comment' => null,
            'generation' => null,
        ];
    }
}
