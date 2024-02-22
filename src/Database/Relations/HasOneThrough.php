<?php

namespace Winter\Storm\Database\Relations;

use Illuminate\Database\Eloquent\Relations\HasOneThrough as HasOneThroughBase;

/**
 * @phpstan-property \Winter\Storm\Database\Model $farParent
 * @phpstan-property \Winter\Storm\Database\Model $parent
 */
class HasOneThrough extends HasOneThroughBase
{
    use Concerns\DefinedConstraints;
    use Concerns\HasRelationName;

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
        return [];
    }
}
