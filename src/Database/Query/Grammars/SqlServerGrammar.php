<?php namespace Winter\Storm\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SqlServerGrammar as BaseSqlServerGrammar;
use Winter\Storm\Database\Query\Grammars\Concerns\SelectConcatenations;

class SqlServerGrammar extends BaseSqlServerGrammar
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
        $columns = $this->columnize(array_keys(reset($values)));

        $sql = 'merge ' . $this->wrapTable($query->from) . ' ';

        $parameters = collect($values)->map(function ($record) {
            return '(' . $this->parameterize($record) . ')';
        })->implode(', ');

        $sql .= 'using (values ' . $parameters . ') ' . $this->wrapTable('laravel_source') . ' (' . $columns . ') ';

        $on = collect($uniqueBy)->map(function ($column) use ($query) {
            return $this->wrap('laravel_source.' . $column) . ' = ' . $this->wrap($query->from . '.' . $column);
        })->implode(' and ');

        $sql .= 'on ' . $on . ' ';

        if ($update) {
            $update = collect($update)->map(function ($value, $key) {
                return is_numeric($key)
                    ? $this->wrap($value) . ' = ' . $this->wrap('laravel_source.' . $value)
                    : $this->wrap($key) . ' = ' . $this->parameter($value);
            })->implode(', ');

            $sql .= 'when matched then update set ' . $update . ' ';
        }

        $sql .= 'when not matched then insert (' . $columns . ') values (' . $columns . ')';

        return $sql;
    }
}
