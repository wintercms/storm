<?php namespace Winter\Storm\Database;

use Illuminate\Database\Eloquent\Model as ModelBase;
use Illuminate\Database\Eloquent\Scope as ScopeInterface;
use Illuminate\Database\Eloquent\Builder as BuilderBase;

class SortableScope implements ScopeInterface
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(BuilderBase $builder, ModelBase $model)
    {
        // Only apply the scope when no other explicit orders have been set
        if (empty($builder->getQuery()->orders) && empty($builder->getQuery()->unionOrders)) {
            $builder->orderBy($model->getSortOrderColumn());
        }
    }
}
