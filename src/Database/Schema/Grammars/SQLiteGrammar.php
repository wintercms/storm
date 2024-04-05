<?php

namespace Winter\Storm\Database\Schema\Grammars;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\ForeignKeyDefinition;
use Illuminate\Database\Schema\IndexDefinition;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar as SQLiteGrammarBase;
use Illuminate\Support\Fluent;

class SQLiteGrammar extends SQLiteGrammarBase
{
    /**
     * Compile a change column command into a series of SQL statements.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @param  \Illuminate\Database\Connection  $connection
     * @return array|string
     *
     * @throws \RuntimeException
     */
    public function compileChange(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        $schema = $connection->getSchemaBuilder();
        $table = $blueprint->getTable();

        $changedColumns = collect($blueprint->getChangedColumns());
        $columnNames = [];
        $autoIncrementColumn = null;

        $columns = collect($schema->getColumns($table))
            ->map(function ($column) use ($blueprint, $changedColumns, &$columnNames, &$autoIncrementColumn) {
                $column = $changedColumns->first(fn ($col) => $col->name === $column['name'], $column);

                if ($column instanceof Fluent) {
                    $name = $this->wrap($column);
                    $autoIncrementColumn = $column->autoIncrement ? $column->name : $autoIncrementColumn;

                    if (is_null($column->virtualAs) && is_null($column->virtualAsJson) &&
                        is_null($column->storedAs) && is_null($column->storedAsJson)) {
                        $columnNames[] = $name;
                    }

                    return $this->addModifiers($name.' '.$this->getType($column), $blueprint, $column);
                } else {
                    $name = $this->wrap($column['name']);
                    $autoIncrementColumn = $column['auto_increment'] ? $column['name'] : $autoIncrementColumn;
                    $isGenerated = ! is_null($column['generation']);

                    if (! $isGenerated) {
                        $columnNames[] = $name;
                    }

                    return $this->addModifiers(
                        $name.' '.$column['type'],
                        $blueprint,
                        new ColumnDefinition([
                            'change' => true,
                            'type' => $column['type_name'],
                            'nullable' => $column['nullable'],
                            'default' => $column['default'] ? new Expression($column['default']) : null,
                            'autoIncrement' => $column['auto_increment'],
                            'collation' => $column['collation'],
                            'comment' => $column['comment'],
                            'virtualAs' => $isGenerated && $column['generation']['type'] === 'virtual'
                                ? $column['generation']['expression'] : null,
                            'storedAs' => $isGenerated && $column['generation']['type'] === 'stored'
                                ? $column['generation']['expression'] : null,
                        ])
                    );
                }
            })->all();

        $foreignKeys = collect($schema->getForeignKeys($table))->map(fn ($foreignKey) => new ForeignKeyDefinition([
            'columns' => $foreignKey['columns'],
            'on' => $foreignKey['foreign_table'],
            'references' => $foreignKey['foreign_columns'],
            'onUpdate' => $foreignKey['on_update'],
            'onDelete' => $foreignKey['on_delete'],
        ]))->all();

        [$primary, $indexes] = collect($schema->getIndexes($table))->map(fn ($index) => new IndexDefinition([
            'name' => match (true) {
                $index['primary'] => 'primary',
                $index['unique'] => 'unique',
                default => 'index',
            },
            'index' => $index['name'],
            'columns' => $index['columns'],
        ]))->partition(fn ($index) => $index->name === 'primary');

        $indexes = collect($indexes)->reject(fn ($index) => str_starts_with('sqlite_', $index->index))->map(
            fn ($index) => $this->{'compile'.ucfirst($index->name)}($blueprint, $index)
        )->all();

        $tempTable = $this->wrap('__temp__'.$blueprint->getPrefix().$table);
        $table = $this->wrapTable($blueprint);
        $columnNames = implode(', ', $columnNames);

        $foreignKeyConstraintsEnabled = $connection->scalar('pragma foreign_keys');

        return array_filter(array_merge(
        [
            $foreignKeyConstraintsEnabled ? $this->compileDisableForeignKeyConstraints() : null,
            sprintf('create table %s (%s%s%s)',
                $tempTable,
                implode(', ', $columns),
                $this->addForeignKeys($foreignKeys),
                $autoIncrementColumn ? '' : $this->addPrimaryKeys($primary->first())
            ),
            sprintf('insert into %s (%s) select %s from %s', $tempTable, $columnNames, $columnNames, $table),
            sprintf('drop table %s', $table),
            sprintf('alter table %s rename to %s', $tempTable, $table),
        ], $indexes, [$foreignKeyConstraintsEnabled ? $this->compileEnableForeignKeyConstraints() : null]));
    }
}
