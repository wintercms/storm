<?php

namespace Winter\Storm\Database\Relations\Concerns;

/**
 * Relation name storage.
 *
 * Relations in Winter CMS have names, either defined in the relation type array or as the name of the method used to
 * access the relation.
 *
 * This replaces the `$relationName` parameter that was used previously in Winter when constructing a relation, and
 * allows us to be compatible with Laravel's relations.
 *
 * @author Ben Thomson <git@alfreido.com>
 * @copyright Winter CMS Maintainers.
 */
trait HasRelationName
{
    /**
     * @var string The name of the relation.
     */
    protected $relationName;

    /**
     * Sets the name of the relation.
     */
    public function setRelationName(string $name): void
    {
        $this->relationName = $name;
    }

    /**
     * Gets the relation name.
     */
    public function getRelationName(): string
    {
        return $this->relationName;
    }
}
