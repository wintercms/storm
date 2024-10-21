<?php

namespace Winter\Storm\Database\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne as MorphOneBase;

/**
 * @phpstan-property \Winter\Storm\Database\Model $parent
 */
class MorphOne extends MorphOneBase implements RelationInterface
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
        if (is_array($value)) {
            return;
        }

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
            $instance = $value;

            if ($this->parent->exists) {
                $instance->setAttribute($this->getForeignKeyName(), $this->getParentKey());
                $instance->setAttribute($this->getMorphType(), $this->morphClass);
            }
        }
        else {
            $instance = $this->getRelated()->find($value);
        }

        if ($instance) {
            $this->parent->setRelation($this->relationName, $instance);

            $this->parent->bindEventOnce('model.afterSave', function () use ($instance) {
                // Relation is already set, do nothing. This prevents the relationship
                // from being nulled below and left unset because the save will ignore
                // attribute values that are numerically equivalent (not dirty).
                if (
                    $instance->getOriginal($this->getForeignKeyName()) == $this->getParentKey() &&
                    $instance->getOriginal($this->getMorphType()) == $this->morphClass
                ) {
                    return;
                }

                $this->update([
                    $this->getForeignKeyName() => null,
                    $this->getMorphType() => null
                ]);
                $instance->setAttribute($this->getForeignKeyName(), $this->getParentKey());
                $instance->setAttribute($this->getMorphType(), $this->morphClass);
                $instance->save(['timestamps' => false]);
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

        if ($this->parent->$relationName) {
            $key = $this->getForeignKeyName();
            $value = $this->parent->$relationName->$key;
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
