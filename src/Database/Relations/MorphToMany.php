<?php

namespace Winter\Storm\Database\Relations;

use Winter\Storm\Database\MorphPivot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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
class MorphToMany extends BaseMorphToMany implements Relation
{
    use Concerns\BelongsOrMorphsToMany;
    use Concerns\CanBePushed;
    use Concerns\DeferOneOrMany;
    use Concerns\DefinedConstraints;
    use Concerns\HasRelationName;

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

    /**
     * {@inheritDoc}
     */
    public function setSimpleValue($value): void
    {
        $relationModel = $this->getRelated();

        /*
         * Nulling the relationship
         */
        if (!$value) {
            // Disassociate in memory immediately
            $this->parent->setRelation($this->relationName, $relationModel->newCollection());

            // Perform sync when the model is saved
            $this->parent->bindEventOnce('model.afterSave', function () {
                $this->detach();
            });
            return;
        }

        /*
         * Convert models to keys
         */
        if ($value instanceof Model) {
            $value = $value->getKey();
        }
        elseif (is_array($value)) {
            foreach ($value as $_key => $_value) {
                if ($_value instanceof Model) {
                    $value[$_key] = $_value->getKey();
                }
            }
        }

        /*
         * Convert scalar to array
         */
        if (!is_array($value) && !$value instanceof Collection) {
            $value = [$value];
        }

        /*
         * Setting the relationship
         */
        $relationCollection = $value instanceof Collection
            ? $value
            : $relationModel->whereIn($relationModel->getKeyName(), $value)->get();

        // Associate in memory immediately
        $this->parent->setRelation($this->relationName, $relationCollection);

        // Perform sync when the model is saved
        $this->parent->bindEventOnce('model.afterSave', function () use ($value) {
            $this->sync($value);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getSimpleValue()
    {
        $value = [];

        $relationName = $this->relationName;

        $sessionKey = $this->parent->sessionKey;

        if ($this->parent->relationLoaded($relationName)) {
            $related = $this->getRelated();

            $value = $this->parent->getRelation($relationName)->pluck($related->getKeyName())->all();
        }
        else {
            $value = $this->allRelatedIds($sessionKey)->all();
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getArrayDefinition(): array
    {
        $definition = [
            get_class($this->query->getModel()),
            'table' => $this->getTable(),
            'key' => $this->getForeignPivotKeyName(),
            'otherKey' => $this->getRelatedPivotKeyName(),
            'parentKey' => $this->getParentKeyName(),
            'relatedKey' => $this->getRelatedKeyName(),
            'inverse' => $this->getInverse(),
            'push' => $this->isPushable(),
        ];

        if (count($this->pivotColumns)) {
            $definition['pivot'] = $this->pivotColumns;
        }

        return $definition;
    }
}
