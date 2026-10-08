<?php

namespace Winter\Storm\Database\Schema\Grammars;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar as BaseSqlServerGrammar;
use Illuminate\Support\Fluent;

class SqlServerGrammar extends BaseSqlServerGrammar
{
    /**
     * Compile a change column command into a series of SQL statements.
     *
     * Starting with Laravel 11, previous column attributes do not persist when changing a column.
     * This restores Laravel previous behavior where existing column attributes are kept
     * unless they get changed by the new Blueprint.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return array|string
     *
     * @throws \RuntimeException
     */
    public function compileChange(Blueprint $blueprint, Fluent $command)
    {
        $changes = (array) $this->compileDropDefaultConstraint($blueprint, $command);
        $schema = $this->connection->getSchemaBuilder();
        $table = $blueprint->getTable();

        $oldColumns = collect($schema->getColumns($table));

        foreach ($blueprint->getChangedColumns() as $column) {
            $sql = sprintf(
                'alter table %s alter column %s %s',
                $this->wrapTable($blueprint),
                $this->wrap($column->name),
                $this->getType($column)
            );

            $oldColumn = $oldColumns->where('name', $column->name)->first();
            if (!$oldColumn instanceof ColumnDefinition) {
                $oldColumn = new ColumnDefinition($oldColumn);
            }

            $attributes = $column->getAttributes();

            foreach ($this->modifiers as $modifier) {
                if (method_exists($this, $method = "modify{$modifier}")) {
                    $mod = strtolower($modifier);
                    $col = isset($oldColumn->{$mod}) && !array_key_exists($mod, $attributes) ? $oldColumn : $column;
                    $sql .= $this->{$method}($blueprint, $col);
                }
            }

            $changes[] = $sql;
        }

        return $changes;
    }

    /**
     * Format a value so that it can be used in "default" clauses.
     *
     * @param  mixed  $value
     * @return string
     */
    public function getDefaultValue($value)
    {
        if (is_string($value) && strlen($value) >= 2 && $value[0] === "'" && substr($value, -1) === "'") {
            $value = substr($value, 1, -1);
        }

        return parent::getDefaultValue($value);
    }
}
