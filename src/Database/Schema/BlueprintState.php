<?php namespace Winter\Storm\Database\Schema;

use Illuminate\Database\Schema\BlueprintState as BaseBlueprintState;
use Illuminate\Support\Fluent;

/**
 * Restores the pre-Laravel 11 behaviour where a column's existing attributes are preserved across a
 * `->change()` unless the new column definition explicitly overrides them.
 *
 * Laravel 11 replaces the column definition wholesale when a column is changed, dropping any
 * attribute (nullable, default, collation, generated expression, ...) the migration did not
 * re-specify. On engines that rebuild the table to apply a change (SQLite) this state drives the
 * rebuild, so merging the previous attributes here restores the expected behaviour through Laravel's
 * own single rebuild - without Storm maintaining a second, hand-rolled rebuild in the grammar.
 */
class BlueprintState extends BaseBlueprintState
{
    /**
     * Attributes carried over from the existing column definition when the changed definition does
     * not set them explicitly.
     *
     * This mirrors the standard column modifiers Laravel's grammars emit. Should a future Laravel
     * version introduce a new preservable modifier, add it here - omitting one is never a
     * regression (Laravel already drops it on change), only a missed preservation opportunity.
     *
     * @var string[]
     */
    protected array $preservedAttributes = [
        'nullable',
        'default',
        'collation',
        'comment',
        'virtualAs',
        'virtualAsJson',
        'storedAs',
        'storedAsJson',
    ];

    /**
     * Update the blueprint's state, preserving existing column attributes when a column is changed.
     *
     * @param  \Illuminate\Support\Fluent  $command
     * @return void
     */
    public function update(Fluent $command)
    {
        if ($command['name'] === 'change' && $command['column'] instanceof Fluent) {
            $this->preserveExistingColumnAttributes($command['column']);
        }

        parent::update($command);
    }

    /**
     * Copy any preserved attribute from the current (pre-change) column definition onto the changed
     * column definition when the migration did not set it explicitly.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return void
     */
    protected function preserveExistingColumnAttributes(Fluent $column): void
    {
        foreach ($this->getColumns() as $existing) {
            if ($existing['name'] !== $column['name']) {
                continue;
            }

            foreach ($this->preservedAttributes as $attribute) {
                if (!isset($column[$attribute]) && isset($existing[$attribute])) {
                    $column[$attribute] = $existing[$attribute];
                }
            }

            return;
        }
    }
}
