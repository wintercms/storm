<?php

namespace Winter\Storm\Database\Schema\Grammars\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;

/**
 * Shared by the grammars that restore Laravel's pre-11 behaviour of keeping the attributes of a
 * changed column that the new Blueprint doesn't set. Each grammar decides which attributes those
 * are, and in what form the server reports them, in getKeptColumnAttributes().
 */
trait KeepsExistingColumnAttributes
{
    /**
     * Get the attributes of an existing column, as reported by the schema builder, that a change keeps
     * when the new definition doesn't set them. A null value is left to the new definition.
     *
     * @param  array|\ArrayAccess  $existing
     * @param  \Illuminate\Support\Fluent  $column
     * @return array
     */
    abstract protected function getKeptColumnAttributes($existing, Fluent $column): array;

    /**
     * Copy the attributes of the existing column that the new definition doesn't set onto the column.
     *
     * The attributes are set on the column itself, because on PostgreSQL and SQL Server its comment and
     * default are compiled by separate commands that run after the change and read the same column.
     */
    protected function keepExistingColumnAttributes(Blueprint $blueprint, Fluent $column): void
    {
        $existing = collect($this->connection->getSchemaBuilder()->getColumns($blueprint->getTable()))
            ->firstWhere('name', $column->get('name'));

        if (!$existing) {
            return;
        }

        $attributes = $column->getAttributes();

        foreach ($this->getKeptColumnAttributes($existing, $column) as $attribute => $value) {
            if (!is_null($value) && !array_key_exists($attribute, $attributes)) {
                $column[$attribute] = $value;
            }
        }
    }

    /**
     * Whether the column is being changed to a type that accepts a collation.
     *
     * Like Laravel 9, the collation is only kept while the column stays a text column.
     */
    protected function changesToATextColumn(Fluent $column): bool
    {
        return in_array($column->get('type'), [
            'char', 'string', 'tinyText', 'text', 'mediumText', 'longText', 'enum', 'set',
        ]);
    }
}
