<?php

namespace Winter\Storm\Database\Relations;

use Illuminate\Database\Eloquent\Builder;
use Winter\Storm\Database\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection as CollectionBase;
use Illuminate\Database\Eloquent\Relations\MorphMany as MorphManyBase;

/**
 * @phpstan-property \Winter\Storm\Database\Model $parent
 */
class MorphMany extends MorphManyBase implements RelationInterface
{
    use Concerns\MorphOneOrMany;
    use Concerns\CanBeCounted;
    use Concerns\CanBeDependent;
    use Concerns\CanBeExtended;
    use Concerns\CanBePushed;
    use Concerns\CanBeSoftDeleted;
    use Concerns\DefinedConstraints;
    use Concerns\HasRelationName;

    /**
     * Create a new morph one or many relationship instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $type
     * @param  string  $id
     * @param  string  $localKey
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $type, $id, $localKey)
    {
        parent::__construct($query, $parent, $type, $id, $localKey);
        $this->extendableRelationConstruct();
    }

    /**
     * {@inheritDoc}
     */
    public function setSimpleValue($value): void
    {
        // Nulling the relationship
        if (!$value) {
            if ($this->parent->exists) {
                $this->parent->bindEventOnce('model.afterSave', function () {
                    $this->update([
                        $this->getForeignKeyName() => null,
                        $this->getMorphType() => null
                    ]);
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
                    $instance->setAttribute($this->getMorphType(), $this->morphClass);
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
                $this->whereNotIn($this->localKey, $existingIds)->update([
                    $this->getForeignKeyName() => null,
                    $this->getMorphType() => null
                ]);
                $collection->each(function ($instance) {
                    $instance->setAttribute($this->getForeignKeyName(), $this->getParentKey());
                    $instance->setAttribute($this->getMorphType(), $this->morphClass);
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

        return [
            get_class($this->query->getModel()),
            'type' => $this->getMorphType(),
            'id' => $this->getForeignKeyName(),
            'delete' => $this->isDependent(),
            'push' => $this->isPushable(),
            'count' => $this->isCountOnly(),
        ];
    }
}
