<?php

namespace Winter\Storm\Database\Schema\Grammars\Concerns;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Fluent;

trait MySqlBasedGrammar
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
            $sql = sprintf(
                '%s %s%s %s',
                is_null($column->renameTo) ? 'modify' : 'change',
                $this->wrap($column),
                is_null($column->renameTo) ? '' : ' '.$this->wrap($column->renameTo),
                $this->getType($column)
            );

            $oldColumn = $oldColumns->where('name', $column->name)->first();
            if (!$oldColumn instanceof ColumnDefinition) {
                $oldColumn = new ColumnDefinition($oldColumn);
            }

            foreach ($this->modifiers as $modifier) {
                if (method_exists($this, $method = "modify{$modifier}")) {
                    $mod = strtolower($modifier);
                    $col = isset($oldColumn->{$mod}) && !isset($column->{$mod}) ? $oldColumn : $column;
                    $sql .= $this->{$method}($blueprint, $col);
                }
            }
            $columns[] = $sql;
        }

        return 'alter table '.$this->wrapTable($blueprint).' '.implode(', ', $columns);
    }
}
