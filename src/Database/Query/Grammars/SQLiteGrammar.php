<?php namespace Winter\Storm\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\SQLiteGrammar as BaseSQLiteGrammar;
use Winter\Storm\Database\Query\Grammars\Concerns\SelectConcatenations;

class SQLiteGrammar extends BaseSQLiteGrammar
{
    use SelectConcatenations;

    /**
     * Compiles a single CONCAT value.
     *
     * SQLite uses slightly different concatenation syntax.
     *
     * @param array $parts The concatenation parts.
     * @param string $as The alias to return the entire concatenation as.
     * @return string
     */
    protected function compileConcat(array $parts, string $as)
    {
        $compileParts = [];

        foreach ($parts as $part) {
            if (preg_match('/^[a-z_@#][a-z0-9@$#_]*$/', $part)) {
                $compileParts[] = $this->wrap($part);
            } else {
                $compileParts[] = $this->wrap(new Expression('\'' . trim($part, '\'"') . '\''));
            }
        }

        return implode(' || ', $compileParts) . ' as ' . $this->wrap($as);
    }

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
        $sql = $this->compileInsert($query, $values);

        $sql .= ' on conflict (' . $this->columnize($uniqueBy) . ') do update set ';

        $columns = collect($update)->map(function ($value, $key) {
            return is_numeric($key)
                ? $this->wrap($value) . ' = ' . $this->wrapValue('excluded') . '.' . $this->wrap($value)
                : $this->wrap($key) . ' = ' . $this->parameter($value);
        })->implode(', ');

        return $sql . $columns;
    }
}
