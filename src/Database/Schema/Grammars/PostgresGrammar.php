<?php

namespace Winter\Storm\Database\Schema\Grammars;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\Grammars\PostgresGrammar as BasePostgresGrammar;
use Illuminate\Support\Fluent;

class PostgresGrammar extends BasePostgresGrammar
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
        $columns = [];
        $schema = $this->connection->getSchemaBuilder();
        $table = $blueprint->getTable();

        $oldColumns = collect($schema->getColumns($table));

        foreach ($blueprint->getChangedColumns() as $column) {
            $changes = ['type '.$this->getType($column).$this->modifyCollate($blueprint, $column)];

            $oldColumn = $oldColumns->where('name', $column->name)->first();
            if (!$oldColumn instanceof ColumnDefinition) {
                $oldColumn = new ColumnDefinition($oldColumn);
            }

            foreach ($this->modifiers as $modifier) {
                if ($modifier === 'Collate') {
                    continue;
                }

                if (method_exists($this, $method = "modify{$modifier}")) {
                    $mod = strtolower($modifier);
                    $col = isset($oldColumn->{$mod}) && ! isset($column->{$mod}) ? $oldColumn : $column;
                    $constraints = (array) $this->{$method}($blueprint, $col);

                    foreach ($constraints as $constraint) {
                        $changes[] = $constraint;
                    }
                }
            }

            $columns[] = implode(', ', $this->prefixArray('alter column '.$this->wrap($column), $changes));
        }

        return 'alter table '.$this->wrapTable($blueprint).' '.implode(', ', $columns);
    }

    public function getDefaultValue($value)
    {
        if (is_string($value)) {
            $value = preg_replace('#\'#', '', $value);
        }

        return parent::getDefaultValue($value);
    }
}
