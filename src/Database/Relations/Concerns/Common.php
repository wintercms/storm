<?php namespace Winter\Storm\Database\Relations\Concerns;
        
trait Common
{
    /**
     * @var string The "name" of the relationship.
     */
    protected $relationName;

    /**
     * Get the relationship name for the relationship.
     *
     * @return string
     */
    public function getRelationName()
    {
        return $this->relationName;
    }
}

