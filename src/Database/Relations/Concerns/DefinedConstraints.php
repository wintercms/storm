<?php

namespace Winter\Storm\Database\Relations\Concerns;

use Winter\Storm\Database\Relations\HasManyThrough;
use Winter\Storm\Database\Relations\HasOneThrough;

/*
 * Handles the constraints and filters defined by a relation.
 * eg: 'conditions' => 'is_published = 1'
 */
trait DefinedConstraints
{
    /**
     * Set the defined constraints on the relation query.
     *
     * This method is kept for backwards compatibility, but is no longer being called directly by Storm when
     * initializing relations. Constraints are now applied on an as-needed basis.
     *
     * @return void
     */
    public function addDefinedConstraints()
    {
        $args = ($this instanceof HasOneThrough || $this instanceof HasManyThrough)
            ? $this->farParent->getRelationDefinition($this->relationName)
            : $this->parent->getRelationDefinition($this->relationName);

        $this->addDefinedConstraintsToRelation($this, $args);
        $this->addDefinedConstraintsToQuery($this, $args);
    }

    /**
     * Add relation based constraints.
     *
     * @param \Illuminate\Database\Eloquent\Relations\Relation $relation
     * @param array|null $args
     */
    public function addDefinedConstraintsToRelation($relation = null, ?array $args = null)
    {
        if (is_null($relation)) {
            $relation = $this;
        }
        if (is_null($args)) {
            $args = $this->getRelationArgs();
        }

        /*
         * Default models (belongsTo)
         */
        if ($defaultData = array_get($args, 'default')) {
            $relation->withDefault($defaultData === true ? null : $defaultData);
        }

        /*
         * Pivot data (belongsToMany, morphToMany, morphByMany)
         */
        if ($pivotData = array_get($args, 'pivot')) {
            $relation->withPivot($pivotData);
        }

        /*
         * Pivot timestamps (belongsToMany, morphToMany, morphByMany)
         */
        if (array_get($args, 'timestamps')) {
            $relation->withTimestamps();
        }
    }

    /**
     * Add query based constraints.
     *
     * @param \Illuminate\Database\Eloquent\Relations\Relation|\Winter\Storm\Database\QueryBuilder $query
     * @param array|null $args
     */
    public function addDefinedConstraintsToQuery($query = null, ?array $args = null)
    {
        if (is_null($query)) {
            $query = $this;
        }
        if (is_null($args)) {
            $args = $this->getRelationArgs();
        }

        /*
         * Conditions
         */
        if ($conditions = array_get($args, 'conditions')) {
            $query->whereRaw($conditions);
        }

        /*
         * Sort order
         */
        $hasCountArg = array_get($args, 'count') !== null;
        if (($orderBy = array_get($args, 'order')) && !$hasCountArg) {
            if (!is_array($orderBy)) {
                $orderBy = [$orderBy];
            }

            foreach ($orderBy as $order) {
                $column = $order;
                $direction = 'asc';

                $parts = explode(' ', $order);
                if (count($parts) > 1) {
                    list($column, $direction) = $parts;
                }

                $query->orderBy($column, $direction);
            }
        }

        /*
         * Scope
         */
        if ($scope = array_get($args, 'scope')) {
            $query->$scope($this->parent);
        }
    }

    /**
     * Get the relation definition for the related model.
     */
    protected function getRelationArgs(): array
    {
        return ($this instanceof HasOneThrough || $this instanceof HasManyThrough)
            ? $this->farParent->getRelationDefinition($this->relationName)
            : $this->parent->getRelationDefinition($this->relationName);
    }
}
