<?php

namespace Winter\Storm\Database\Relations;

use Winter\Storm\Database\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection as CollectionBase;
use Illuminate\Database\Eloquent\Relations\HasMany as HasManyBase;

/**
 * @phpstan-property \Winter\Storm\Database\Model $parent
 */
class HasMany extends HasManyBase implements Relation
{
    use Concerns\HasOneOrMany;
    use Concerns\CanBeDependent;
    use Concerns\DefinedConstraints;
    use Concerns\HasRelationName;

    /**
     * {@inheritDoc}
     */
    public function setSimpleValue($value): void
    {
        // Nulling the relationship
        if (!$value) {
            if ($this->parent->exists) {
                $this->parent->bindEventOnce('model.afterSave', function () {
                    $this->update([$this->getForeignKeyName() => null]);
                });
            }
            return;
        }

        if ($value instanceof Model) {
            $value = new Collection([$value]);
        }

        if ($value instanceof CollectionBase) {
            $collection = $value;

            if ($this->parent->exists) {
                $collection->each(function ($instance) {
                    $instance->setAttribute($this->getForeignKeyName(), $this->getParentKey());
                });
            }
        }
        else {
            $collection = $this->getRelated()->whereIn($this->localKey, (array) $value)->get();
        }

        if ($collection) {
            $this->parent->setRelation($this->relationName, $collection);

            $this->parent->bindEventOnce('model.afterSave', function () use ($collection) {
                $existingIds = $collection->pluck($this->localKey)->all();
                $this->whereNotIn($this->localKey, $existingIds)->update([$this->getForeignKeyName() => null]);
                $collection->each(function ($instance) {
                    $instance->setAttribute($this->getForeignKeyName(), $this->getParentKey());
                    $instance->save(['timestamps' => false]);
                });
            });
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getSimpleValue()
    {
        $value = null;
        $relationName = $this->relationName;

        if ($relation = $this->parent->$relationName) {
            $value = $relation->pluck($this->localKey)->all();
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getArrayDefinition(): array
    {
        return [];
    }
}
