<?php

namespace Winter\Storm\Database\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough as HasManyThroughBase;

/**
 * @phpstan-property \Winter\Storm\Database\Model $farParent
 * @phpstan-property \Winter\Storm\Database\Model $parent
 */
class HasManyThrough extends HasManyThroughBase
{
    use Concerns\CanBeExtended;
    use Concerns\CanBePushed;
    use Concerns\DefinedConstraints;
    use Concerns\HasRelationName;

    /**
     * {@inheritDoc}
     */
    public function __construct(Builder $query, Model $farParent, Model $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey)
    {
        parent::__construct($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
        $this->extendableRelationConstruct();
    }

    /**
     * Determine whether close parent of the relation uses Soft Deletes.
     *
     * @return bool
     */
    public function parentSoftDeletes()
    {
        $uses = class_uses_recursive(get_class($this->parent));

        return in_array('Winter\Storm\Database\Traits\SoftDelete', $uses) ||
            in_array('Illuminate\Database\Eloquent\SoftDeletes', $uses);
    }

    /**
     * {@inheritDoc}
     */
    public function getArrayDefinition(): array
    {
        return [
            get_class($this->query->getModel()),
            'through' => get_class($this->throughParent),
            'key' => $this->getForeignKeyName(),
            'throughKey' => $this->getFirstKeyName(),
            'otherKey' => $this->getLocalKeyName(),
            'secondOtherKey' => $this->getSecondLocalKeyName(),
            'push' => $this->isPushable(),
        ];
    }
}
