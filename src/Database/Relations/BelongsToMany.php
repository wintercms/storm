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

        // if pivot data doesn't exists, Convert models to keys , else convert models to nested arrays with the keys as the indexes (pivot of relation needs to use Winter\Storm\Database\Pivot)
        if ($value instanceof Model) {
            if ($value->{$this->getPivotAccessor()} instanceof Pivot) {
                $value = [$value->getKey() => $value->{$this->getPivotAccessor()}->toArray()];
            } else {
                $value = $value->getKey();
            }
        } elseif (is_array($value)) {
            $newValue = [];
            foreach ($value as $_key => $_value) {
                if ($_value instanceof Model) {
                    if ($_value->{$this->getPivotAccessor()} instanceof Pivot) {
                        $newValue[$_value->getKey()] = $_value->{$this->getPivotAccessor()}->toArray();
                    } else {
                        $newValue[] = $_value->getKey();
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

        //checks if $value has nested arrays(pivot data) and regenerates the keys in a basic array format
        $keys = [];
        foreach ($value as $_key => $_value) {
            if (is_array($_value)) {
                $keys[] = $_key;
            }
        }
        if (count($keys) < 1) {
            $keys = $value;
            $hasPivot = false;
        } else {
            $hasPivot = true;
        }

        // Setting the relationship
        $relationCollection = $value instanceof Collection
            ? $value
            : $relationModel->whereIn($relationModel->getKeyName(), $keys)->get();


        // If avaliable, associate the pivot relation(s) in memory immediately (pivot of relation needs to use Winter\Storm\Database\Pivot)
        if ($hasPivot) {
            foreach ($relationCollection as  $_relationModel) {
                if (isset($value[$_relationModel->getKey()])) {
                    $_relationModel->setRelation($this->getPivotAccessor(), $this->newPivot($value[$_relationModel->getKey()]));
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
