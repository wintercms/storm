<?php

namespace Winter\Storm\Database\Schema\Grammars\Concerns;

use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MariaDbGrammar;
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
            'default' => $this->getKeptColumnDefault($existing['default']),
            'comment' => $existing['comment'],
            'unsigned' => str_contains(strtolower((string) $existing['type']), 'unsigned') ?: null,
        ];

        // Like Laravel 9, only keep the collation while the column stays a text column
        if (in_array($column->get('type'), ['char', 'string', 'tinyText', 'text', 'mediumText', 'longText', 'enum', 'set'])) {
            $attributes['collation'] = $existing['collation'];
        }

        return array_filter($attributes, fn ($value) => !is_null($value));
    }

    /**
     * Turn the default of an existing column, as reported by the schema builder, back into a definition.
     *
     * MariaDB reports every default as an SQL expression ('text', NULL, current_timestamp()). MySQL reports
     * a literal default as its raw value, and an expression such as CURRENT_TIMESTAMP as is.
     *
     * @param  mixed  $default
     * @return mixed
     */
    protected function getKeptColumnDefault($default)
    {
        if (!is_string($default)) {
            return $default;
        }

        if ($this instanceof MariaDbGrammar || ($this->connection instanceof MySqlConnection && $this->connection->isMaria())) {
            return $default === 'NULL' ? null : new Expression($default);
        }

        return str_starts_with(strtolower($default), 'current_timestamp') ? new Expression($default) : $default;
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
