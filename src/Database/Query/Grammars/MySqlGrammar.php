<?php namespace Winter\Storm\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\MySqlGrammar as BaseMysqlGrammer;
use Winter\Storm\Database\Query\Grammars\Concerns\SelectConcatenations;

class MySqlGrammar extends BaseMysqlGrammer
{
    use SelectConcatenations;

    /**
     * Compile an "upsert" statement into SQL.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  array $values
     * @param  array $uniqueBy
     * @param  array $update
     * @return  string
     */
    public function compileUpsert(Builder $query, array $values, array $uniqueBy, array $update)
    {
        $sql = $this->compileInsert($query, $values) . ' on duplicate key update ';

        $columns = collect($update)->map(function ($value, $key) {
            return is_numeric($key)
                ? $this->wrap($value) . ' = values(' . $this->wrap($value) . ')'
                : $this->wrap($key) . ' = ' . $this->parameter($value);
        })->implode(', ');

        return $sql . $columns;
    }
}
