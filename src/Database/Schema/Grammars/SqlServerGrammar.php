<?php

namespace Winter\Storm\Database\Schema\Grammars;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar as SqlServerGrammarBase;
use Illuminate\Support\Fluent;

class SqlServerGrammar extends SqlServerGrammarBase
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
        $changes = [$this->compileDropDefaultConstraint($blueprint, $command)];
        $oldColumns = collect($connection->getSchemaBuilder()->getColumns($blueprint->getTable()));

        foreach ($blueprint->getChangedColumns() as $column) {
            $sql = sprintf(
                'alter table %s alter column %s %s',
                $this->wrapTable($blueprint),
                $this->wrap($column),
                $this->getType($column)
            );

            $oldColumn = new Fluent($oldColumns->where('name', $column->name)->first());

            foreach ($this->modifiers as $modifier) {
                if (method_exists($this, $method = "modify{$modifier}")) {
                    $mod = strtolower($modifier);
                    $col = isset($oldColumn->{$mod}) ? $oldColumn : $column;
                    $sql .= $this->{$method}($blueprint, $col);
                }
            }

            $changes[] = $sql;
        }

        return $changes;
    }
}
