<?php

namespace Winter\Storm\Database\Schema\Grammars;

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
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
     * The kept attributes are set on the column itself, so that the default command that runs
     * after the change adds the existing default back once the change has dropped its constraint.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return array|string
     */
    public function compileChange(Blueprint $blueprint, Fluent $command)
    {
        /** @var \Illuminate\Database\Schema\ColumnDefinition $column */
        $column = $command->get('column');

        $existing = collect($this->connection->getSchemaBuilder()->getColumns($blueprint->getTable()))
            ->firstWhere('name', $column->get('name'));

        if ($existing) {
            $attributes = $column->getAttributes();

            foreach ($this->getKeptColumnAttributes($existing, $column) as $attribute => $value) {
                if (!array_key_exists($attribute, $attributes)) {
                    $column[$attribute] = $value;
                }
            }
        }

        return parent::compileChange($blueprint, $command);
    }

    /**
     * Get the attributes of an existing column, as reported by the schema builder, that a change keeps
     * when the new definition doesn't set them.
     *
     * @param  array|\ArrayAccess  $existing
     * @param  \Illuminate\Support\Fluent  $column
     * @return array
     */
    protected function getKeptColumnAttributes($existing, Fluent $column): array
    {
        $attributes = [
            'nullable' => (bool) $existing['nullable'],
            // SQL Server reports a default as the expression of its constraint, such as ('text')
            'default' => is_null($existing['default']) ? null : new Expression($existing['default']),
        ];

        if (in_array($column->get('type'), ['char', 'string', 'tinyText', 'text', 'mediumText', 'longText', 'enum', 'set'])) {
            $attributes['collation'] = $existing['collation'];
        }

        return array_filter($attributes, fn ($value) => !is_null($value));
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
