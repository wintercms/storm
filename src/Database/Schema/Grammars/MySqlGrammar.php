<?php

namespace Winter\Storm\Database\Schema\Grammars;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MySqlGrammar as MySqlGrammarBase;
use Illuminate\Database\Connection;
use Illuminate\Support\Fluent;

class MySqlGrammar extends MySqlGrammarBase
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

            $oldColumn = new Fluent($oldColumns->where('name', $column->name)->first());
            $columns[] = $this->addLegacyModifiers($sql, $blueprint, $column, $oldColumn);
        }

        return 'alter table '.$this->wrapTable($blueprint).' '.implode(', ', $columns);
    }

    protected function addLegacyModifiers($sql, Blueprint $blueprint, Fluent $column, Fluent $oldColumn)
    {
        foreach ($this->modifiers as $modifier) {
            if (method_exists($this, $method = "modify{$modifier}")) {
                $mod = strtolower($modifier);
                $col = isset($oldColumn->{$mod}) ? $oldColumn : $column;
                $sql .= $this->{$method}($blueprint, $col);
            }
        }

        return $sql;
    }
}
