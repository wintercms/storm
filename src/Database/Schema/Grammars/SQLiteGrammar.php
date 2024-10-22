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
     * @inheritDoc
     */
    public function getAlterCommands(Connection $connection)
    {
        // Can be removed when the following PR gets merged:
        // https://github.com/laravel/framework/pull/53262
        $alterCommands = ['change', 'primary', 'dropPrimary', 'foreign', 'dropForeign'];

        if (version_compare($connection->getServerVersion(), '3.35', '>=')) {
            $alterCommands[] = 'dropColumn';
        }

        return $alterCommands;
    }

    /**
     * Compile a change column command into a series of SQL statements.
     *
     * Starting with Laravel 11, previous column attributes do not persist when changing a column.
     * This restores Laravel previous behavior where existing column attributes are kept
     * unless they get changed by the new Blueprint.
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

        $oldColumns = collect($connection->getSchemaBuilder()->getColumns($blueprint->getTable()));

        $columns = collect($schema->getColumns($table))
            ->map(function ($column) use ($blueprint, $changedColumns, &$columnNames, &$autoIncrementColumn, $oldColumns) {
                $column = $changedColumns->first(fn ($col) => $col->name === $column['name'], $column);

                if (! $column instanceof Fluent) {
                    $isGenerated = ! is_null($column['generation']);
                    $column = new ColumnDefinition([
                        'change' => true,
                        'name' => $column['name'],
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
                    ]);
                }

                $name = $this->wrap($column);
                $autoIncrementColumn = $column->autoIncrement ? $column->name : $autoIncrementColumn;

                if (is_null($column->virtualAs) && is_null($column->virtualAsJson) &&
                    is_null($column->storedAs) && is_null($column->storedAsJson)
                ) {
                    $columnNames[] = $name;
                }

                $oldColumn = $oldColumns->where('name', $column->name)->first();
                if (!$oldColumn instanceof ColumnDefinition) {
                    $oldColumn = new ColumnDefinition($oldColumn);
                }
                $sql = $name.' '.$this->getType($column);

                foreach ($this->modifiers as $modifier) {
                    if (method_exists($this, $method = "modify{$modifier}")) {
                        $mod = strtolower($modifier);
                        $col = isset($oldColumn->{$mod}) && !isset($column->{$mod}) ? $oldColumn : $column;
                        $sql .= $this->{$method}($blueprint, $col);
                    }
                }
                return $sql;
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

        return array_filter(
            array_merge(
                [
                    $foreignKeyConstraintsEnabled ? $this->compileDisableForeignKeyConstraints() : null,
                    sprintf(
                        'create table %s (%s%s%s)',
                        $tempTable,
                        implode(', ', $columns),
                        $this->addForeignKeys($foreignKeys),
                        $autoIncrementColumn ? '' : $this->addPrimaryKeys($primary->first())
                    ),
                    sprintf(
                        'insert into %s (%s) select %s from %s',
                        $tempTable,
                        $columnNames,
                        $columnNames,
                        $table
                    ),
                    sprintf(
                        'drop table %s',
                        $table
                    ),
                    sprintf(
                        'alter table %s rename to %s',
                        $tempTable,
                        $table
                    ),
                ],
                $indexes,
                [
                    $foreignKeyConstraintsEnabled ? $this->compileEnableForeignKeyConstraints() : null
                ]
            )
        );
    }

    public function getDefaultValue($value)
    {
        if (is_string($value)) {
            $value = preg_replace('#\'#', '', $value);
        }

        return parent::getDefaultValue($value);
    }

    /**
     * Create the column definition for a varchar type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeVarChar(Fluent $column)
    {
        return 'varchar';
    }
}
