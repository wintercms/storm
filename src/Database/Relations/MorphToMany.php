<?php namespace Winter\Storm\Database\Relations;

use Winter\Storm\Database\MorphPivot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany as BaseMorphToMany;

/**
 * Morph To Many relation.
 *
 * As of 1.2.0, this relation has been refactored to extend the Eloquent `MorphToMany` relation,
 * to maintain covariance with Laravel. We instead use the `Concerns\BelongsOrMorphsToMany` trait
 * to provide base `BaseToMany` functionality that includes Winter overrides.
 *
 * @phpstan-property \Winter\Storm\Database\Model $parent
 */
class MorphToMany extends BaseMorphToMany
{
    use Concerns\BelongsOrMorphsToMany;
    use Concerns\DeferOneOrMany;
    use Concerns\DefinedConstraints;

    /**
     * Create a new morph to many relationship instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $name
     * @param  string  $table
     * @param  string  $foreignKey
     * @param  string  $otherKey
     * @param  string  $relationName
     * @param  bool  $inverse
     * @return void
     */
    public function __construct(
        Builder $query,
        Model $parent,
        $name,
        $table,
        $foreignKey,
        $otherKey,
        $parentKey,
        $relatedKey,
        $relationName = null,
        $inverse = false
    ) {
        parent::__construct(
            $query,
            $parent,
            $name,
            $table,
            $foreignKey,
            $otherKey,
            $parentKey,
            $relatedKey,
            $relationName,
            $inverse
        );

        $this->addDefinedConstraints();
    }

    /**
     * Create a new query builder for the pivot table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function newPivotQuery()
    {
        return parent::newPivotQuery()->where($this->morphType, $this->morphClass);
    }

    /**
     * Create a new pivot model instance.
     *
     * @param  array  $attributes
     * @param  bool   $exists
     * @return \Illuminate\Database\Eloquent\Relations\Pivot
     */
    public function newPivot(array $attributes = [], $exists = false)
    {
        $using = $this->using;

        $pivot = $using ? $using::fromRawAttributes($this->parent, $attributes, $this->table, $exists)
                        : MorphPivot::fromAttributes($this->parent, $attributes, $this->table, $exists);

        $pivot->setPivotKeys($this->foreignPivotKey, $this->relatedPivotKey)
              ->setMorphType($this->morphType)
              ->setMorphClass($this->morphClass);

        return $pivot;
    }
}
