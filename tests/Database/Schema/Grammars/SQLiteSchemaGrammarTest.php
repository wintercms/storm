<?php

namespace Winter\Storm\Tests\Database\Schema\Grammars;

use Illuminate\Database\Schema\SQLiteBuilder;
use Illuminate\Database\SQLiteConnection;
use Winter\Storm\Database\Schema\Blueprint;
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

    /**
     * Boot a real in-memory SQLite connection wired with Winter's schema grammar and Blueprint, then
     * seed it with the provided table definition. Column changes on SQLite force a full table
     * rebuild, so a real connection (not a mocked one) is required to exercise the introspection that
     * drives it.
     *
     * @return array{0: \PDO, 1: \Illuminate\Database\SQLiteConnection, 2: \Illuminate\Database\Schema\SQLiteBuilder}
     */
    protected function bootSqlite(string $createTableSql): array
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->exec($createTableSql);

        $connection = new SQLiteConnection($pdo, ':memory:', '');
        $connection->setSchemaGrammar(new SQLiteGrammar($connection));

        $builder = $connection->getSchemaBuilder();
        $builder->blueprintResolver(fn ($conn, $table, $callback) => new Blueprint($conn, $table, $callback));

        return [$pdo, $connection, $builder];
    }

    /**
     * Return the PRAGMA table_info row for a single column.
     *
     * @return array{name: string, type: string, notnull: string, dflt_value: ?string, pk: string}
     */
    protected function columnInfo(\PDO $pdo, string $table, string $column): array
    {
        foreach ($pdo->query('PRAGMA table_info("' . $table . '")') as $info) {
            if ($info['name'] === $column) {
                return $info;
            }
        }

        $this->fail("Column [{$column}] was not found on table [{$table}].");
    }

    public function testChangeMakesColumnNullable(): void
    {
        [$pdo, , $builder] = $this->bootSqlite('CREATE TABLE users (id integer primary key, name varchar not null)');

        $builder->table('users', fn (Blueprint $table) => $table->string('name')->nullable()->change());

        $this->assertSame(0, (int) $this->columnInfo($pdo, 'users', 'name')['notnull']);
    }

    public function testChangeAddingDefaultPreservesExistingNullable(): void
    {
        [$pdo, , $builder] = $this->bootSqlite('CREATE TABLE users (id integer primary key, name varchar)');

        // Only a default is specified; the column's existing nullable state must be preserved.
        $builder->table('users', fn (Blueprint $table) => $table->string('name')->default('admin')->change());

        $info = $this->columnInfo($pdo, 'users', 'name');
        $this->assertSame(0, (int) $info['notnull'], 'The column should remain nullable.');
        $this->assertSame("'admin'", $info['dflt_value']);
    }

    public function testChangeCanAddDefaultAndDropNullable(): void
    {
        [$pdo, , $builder] = $this->bootSqlite('CREATE TABLE users (id integer primary key, name varchar)');

        $builder->table('users', fn (Blueprint $table) => $table->string('name')->default('admin')->nullable(false)->change());

        $info = $this->columnInfo($pdo, 'users', 'name');
        $this->assertSame(1, (int) $info['notnull']);
        $this->assertSame("'admin'", $info['dflt_value']);
    }

    public function testChangePreservesUnspecifiedAttributes(): void
    {
        [$pdo, , $builder] = $this->bootSqlite("CREATE TABLE users (id integer primary key, name varchar default 'bob')");

        // Change only the type; the existing default must survive (pre-Laravel 11 behaviour).
        $builder->table('users', fn (Blueprint $table) => $table->text('name')->change());

        $info = $this->columnInfo($pdo, 'users', 'name');
        $this->assertSame('text', strtolower($info['type']));
        $this->assertSame("'bob'", $info['dflt_value']);
    }

    public function testChangePreservesTinyintTypeOfOtherColumns(): void
    {
        [$pdo, , $builder] = $this->bootSqlite('CREATE TABLE users (is_active tinyint not null, name varchar not null)');

        // Changing an unrelated column rebuilds the whole table; is_active must keep its declared
        // type verbatim rather than being re-derived to "integer".
        $builder->table('users', fn (Blueprint $table) => $table->string('name')->nullable()->change());

        $info = $this->columnInfo($pdo, 'users', 'is_active');
        $this->assertSame('tinyint', strtolower($info['type']));
        $this->assertSame(1, (int) $info['notnull']);
    }

    public function testChangeOnTableWithDecimalAndBinaryColumnsIsFaithful(): void
    {
        [$pdo, , $builder] = $this->bootSqlite(
            'CREATE TABLE t (id integer primary key, amount numeric(9,2), payload blob, name varchar)'
        );

        // Prior to this fix the rebuild re-derived every column through the grammar, throwing
        // "Method SQLiteGrammar::typeNumeric does not exist" for the decimal column.
        $builder->table('t', fn (Blueprint $table) => $table->text('name')->change());

        $this->assertSame('numeric(9,2)', strtolower($this->columnInfo($pdo, 't', 'amount')['type']));
        $this->assertSame('blob', strtolower($this->columnInfo($pdo, 't', 'payload')['type']));
    }

    public function testChangeRebuildsTableExactlyOnce(): void
    {
        [, $connection] = $this->bootSqlite('CREATE TABLE t (id integer primary key, name varchar)');

        $blueprint = new Blueprint($connection, 't');
        $blueprint->text('name')->change();

        $rebuilds = count(array_filter(
            $blueprint->toSql(),
            fn ($statement) => str_contains($statement, 'create table "__temp__')
        ));

        $this->assertSame(1, $rebuilds, 'A single ->change() must rebuild the table exactly once.');
    }
}
