<?php

namespace Tests\Database\Schema\Grammars;

use Illuminate\Database\Schema\MariaDbBuilder;
use Winter\Storm\Database\Schema\Grammars\MariaDbGrammar;

class MariaDbSchemaGrammarTest extends \Winter\Storm\Tests\GrammarTestCase
{
    public function setUp(): void
    {
        $this->grammarClass = MariaDbGrammar::class;
        $this->builderClass = MariaDbBuilder::class;

        parent::setUp();
    }

    public function testChangeKeepsANullableColumnWithoutADefault()
    {
        // MariaDB reports a nullable column without a default as the expression NULL
        $this->existingColumns([$this->mariaDbColumn('name', 'varchar(255)', nullable: true, default: 'NULL')]);

        $blueprint = $this->getBlueprint('users');
        $blueprint->string('name', 100)->change();

        $this->assertSame('alter table `users` modify `name` varchar(100) null', $this->runBlueprint($blueprint)[0]);
    }

    public function testChangeKeepsAQuotedDefault()
    {
        $this->existingColumns([$this->mariaDbColumn('name', 'varchar(255)', default: "'O''Brien'")]);

        $blueprint = $this->getBlueprint('users');
        $blueprint->string('name', 100)->change();

        $this->assertSame("alter table `users` modify `name` varchar(100) not null default 'O''Brien'", $this->runBlueprint($blueprint)[0]);
    }

    public function testChangeKeepsACurrentTimestampDefault()
    {
        $this->existingColumns([$this->mariaDbColumn('created_at', 'timestamp', default: 'current_timestamp()')]);

        $blueprint = $this->getBlueprint('users');
        $blueprint->timestamp('created_at')->nullable()->change();

        $this->assertSame('alter table `users` modify `created_at` timestamp null default current_timestamp()', $this->runBlueprint($blueprint)[0]);
    }

    /**
     * A column as MariaDB's getColumns() reports it: every default as an SQL expression.
     */
    protected function mariaDbColumn(string $name, string $type, bool $nullable = false, ?string $default = null): array
    {
        return compact('name', 'type', 'nullable', 'default') + [
            'type_name' => strtok($type, '('),
            'collation' => null,
            'auto_increment' => false,
            'comment' => null,
            'generation' => null,
        ];
    }
}
