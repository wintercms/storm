<?php

namespace Winter\Storm\Database\Schema\Grammars;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\PostgresGrammar as PostgresGrammarBase;
use Illuminate\Support\Fluent;

class PostgresGrammar extends PostgresGrammarBase
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
     * @param  \Illuminate\Database\Connection  $connection
     * @return array|string
     *
     * @throws \RuntimeException
     */
    public function compileChange(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        $columns = [];
        $oldColumns = collect($connection->getSchemaBuilder()->getColumns($blueprint->getTable()));

        foreach ($blueprint->getChangedColumns() as $column) {
            $changes = ['type '.$this->getType($column).$this->modifyCollate($blueprint, $column)];

            $oldColumn = $oldColumns->where('name', $column->name)->first();
            foreach ($this->modifiers as $modifier) {
                if ($modifier === 'Collate') {
                    continue;
                }

                if (method_exists($this, $method = "modify{$modifier}")) {
                    $mod = strtolower($modifier);
                    $col = isset($oldColumn->{$mod}) ? $oldColumn : $column;
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
}
