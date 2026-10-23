<?php

namespace Winter\Storm\Database\Schema\Grammars;

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\PostgresGrammar as BasePostgresGrammar;
use Illuminate\Support\Fluent;

class PostgresGrammar extends BasePostgresGrammar
{
    use Concerns\KeepsExistingColumnAttributes;

    /**
     * Compile a change column command into a series of SQL statements.
     *
     * Starting with Laravel 11, previous column attributes do not persist when changing a column.
     * This restores Laravel previous behavior where existing column attributes are kept
     * unless they get changed by the new Blueprint.
     *
     * The kept attributes are set on the column itself, so that the comment command that runs
     * after the change keeps the existing comment as well.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return array|string
     */
    public function compileChange(Blueprint $blueprint, Fluent $command)
    {
        /** @var \Illuminate\Database\Schema\ColumnDefinition $column */
        $column = $command->get('column');

        $this->keepExistingColumnAttributes($blueprint, $column);

        return parent::compileChange($blueprint, $command);
    }

    /** @inheritDoc */
    protected function getKeptColumnAttributes($existing, Fluent $column): array
    {
        $attributes = [
            'nullable' => (bool) $existing['nullable'],
            // PostgreSQL reports every default as an SQL expression, such as 'text'::character varying
            'default' => is_null($existing['default']) ? null : new Expression($existing['default']),
            'comment' => $existing['comment'],
        ];

        if ($this->changesToATextColumn($column)) {
            $attributes['collation'] = $existing['collation'];
        }

        return $attributes;
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
