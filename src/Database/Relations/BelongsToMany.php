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

        // Convert models to keys
        if ($value instanceof Model) {
            $value = $value->getKey();
        } elseif (is_array($value)) {
            foreach ($value as $_key => $_value) {
                if ($_value instanceof Model) {
                    $value[$_key] = $_value->getKey();
                }
            }
        }

        // Convert scalar to array
        if (!is_array($value) && !$value instanceof Collection) {
            $value = [$value];
        }

        // Setting the relationship
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
