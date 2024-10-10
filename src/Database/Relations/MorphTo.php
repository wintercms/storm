<?php

namespace Winter\Storm\Database\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo as MorphToBase;

/**
 * @phpstan-property \Winter\Storm\Database\Model $parent
 */
class MorphTo extends MorphToBase implements RelationInterface
{
    use Concerns\BelongsOrMorphsTo;
    use Concerns\CanBeCounted;
    use Concerns\CanBeExtended;
    use Concerns\CanBePushed;
    use Concerns\DeferOneOrMany;
    use Concerns\DefinedConstraints;
    use Concerns\HasRelationName;

    /**
     * {@inheritDoc}
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $ownerKey, $type, $relation)
    {
        parent::__construct($query, $parent, $foreignKey, $ownerKey, $type, $relation);
        $this->extendableRelationConstruct();
    }

    /**
     * {@inheritDoc}
     */
    public function setSimpleValue($value): void
    {
        // Nulling the relationship
        if (!$value) {
            $this->dissociate();
            return;
        }

        if ($value instanceof Model) {
            /*
             * Non existent model, use a single serve event to associate it again when ready
             */
            if (!$value->exists) {
                $value->bindEventOnce('model.afterSave', function () use ($value) {
                    $this->associate($value);
                });
            }

            $this->associate($value);
            $this->parent->setRelation($this->relationName, $value);
        }
        elseif (is_array($value)) {
            list($modelId, $modelClass) = $value;
            $this->parent->setAttribute($this->foreignKey, $modelId);
            $this->parent->setAttribute($this->morphType, $modelClass);
            $this->parent->reloadRelations($this->relationName);
        }
        else {
            $this->parent->setAttribute($this->foreignKey, $value);
            $this->parent->reloadRelations($this->relationName);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getSimpleValue()
    {
        return [
            $this->parent->getAttribute($this->foreignKey),
            $this->parent->getAttribute($this->morphType)
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getArrayDefinition(): array
    {
        return [
            get_class($this->query->getModel()),
            'key' => $this->getForeignKeyName(),
            'otherKey' => $this->getOwnerKeyName(),
            'push' => $this->isPushable(),
            'count' => $this->isCountOnly(),
        ];
    }
}
