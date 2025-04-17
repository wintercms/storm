<?php

namespace Winter\Storm\Database\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as BelongsToManyBase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Winter\Storm\Database\Pivot;

class BelongsToMany extends BelongsToManyBase implements RelationInterface
{
    use Concerns\BelongsOrMorphsToMany;
    use Concerns\CanBeCounted;
    use Concerns\CanBeDetachable;
    use Concerns\CanBeExtended;
    use Concerns\CanBePushed;
    use Concerns\DeferOneOrMany;
    use Concerns\DefinedConstraints;
    use Concerns\HasRelationName;

    /**
     * {@inheritDoc}
     */
    public function __construct(
        Builder $query,
        Model $parent,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $relationName = null
    ) {
        parent::__construct($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
        $this->extendableRelationConstruct();
    }

    /**
     * {@inheritDoc}
     */
    public function getPivotClass()
    {
        return !empty($this->using)
            ? $this->using
            : Pivot::class;
    }

    /**
     * {@inheritDoc}
     */
    public function setSimpleValue($value): void
    {
        $relationModel = $this->getRelated();

        // Nulling the relationship
        if (!$value) {
            // Disassociate in memory immediately
            $this->parent->setRelation($this->relationName, $relationModel->newCollection());

            // Perform sync when the model is saved
            $this->parent->bindEventOnce('model.afterSave', function () {
                $this->detach();
            });
            return;
        }

        // Convert models to keys and check if pivot data exists
        if ($value instanceof Model) {
            if ($value->pivot instanceof Pivot) {
                $value = [$value->getKey() => $value->pivot->toArray()];
            } else {
                $value = $value->getKey();
            }
        } elseif (is_array($value)) {
            $newValue = [];
            foreach ($value as $_key => $_value) {
                if ($_value instanceof Model) {
                    if ($_value->pivot instanceof Pivot) {
                        $newValue[$_value->getKey()] = $_value->pivot->toArray();
                    } else {
                        $newValue[$_key] = $_value->getKey();
                    }
                }
            }
            if (count($newValue) > 0) {
                $value = $newValue;
            }
        }

        // Convert scalar to array
        if (!is_array($value) && !$value instanceof Collection) {
            $value = [$value];
        }

        //Check if value has nested arrays (pivot data)
        $keys = $value;
        $hasPivot = false;
        foreach ($value as $_key => $_value) {
            if (is_array($_value)) {
                $keys[$_key] = $_key;
                $hasPivot = true;
            }
        }

        // Setting the relationship
        $relationCollection = $value instanceof Collection
            ? $value
            : $relationModel->whereIn($relationModel->getKeyName(), $keys)->get();


        // Associate in memory immediately with pivot data (pivot of relation needs to use Winter\Storm\Database\Pivot)
        if ($hasPivot) {
            foreach ($relationCollection as  $_relationModel) {
                if (isset($value[$_relationModel->id])) {
                    $_relationModel->setRelation('pivot', $this->newPivot($value[$_relationModel->id]));
                }
            }
        }

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

        $sessionKey = $this->parent->sessionKey;

        if ($this->parent->relationLoaded($this->relationName)) {
            $related = $this->getRelated();

            $value = $this->parent->getRelation($this->relationName)->pluck($related->getKeyName())->all();
        } else {
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
            get_class($this->getRelated()),
            'table' => $this->getTable(),
            'key' => $this->getForeignPivotKeyName(),
            'otherKey' => $this->getRelatedKeyName(),
            'push' => $this->isPushable(),
            'detach' => $this->isDetachable(),
            'count' => $this->isCountOnly(),
        ];

        if (count($this->pivotColumns)) {
            $definition['pivot'] = $this->pivotColumns;
        }

        return $definition;
    }
}
