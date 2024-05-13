<?php namespace Winter\Storm\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOneThrough as HasOneThroughBase;

/**
 * @phpstan-property \Winter\Storm\Database\Model $farParent
 * @phpstan-property \Winter\Storm\Database\Model $parent
 */
class HasOneThrough extends HasOneThroughBase
{
    use Concerns/Common;
    use Concerns\DefinedConstraints;

    /**
     * Create a new has many relationship instance.
     * @return void
     */
    public function __construct(Builder $query, Model $farParent, Model $parent, $firstKey, $secondKey, $localKey, $secondLocalKey, $relationName = null)
    {
        $this->relationName = $relationName;

        parent::__construct($query, $farParent, $parent, $firstKey, $secondKey, $localKey, $secondLocalKey);

        $this->addDefinedConstraints();
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
}
