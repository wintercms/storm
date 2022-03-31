<?php namespace Winter\Storm\Database\Traits;

use Exception;
use Winter\Storm\Database\SortableScope;

/**
 * SortableRelation model trait
 *
 * Usage:
 *
 * In the model class definition add:
 *
 *   use \Winter\Storm\Database\Traits\SortableRelation;
 *
 *   public $sortableRelations = ['relation_name' => 'sort_order_column'];
 *
 * To set orders:
 *
 *   $model->setSortableRelationOrder($relationName, $recordIds, $recordOrders);
 *
 */
trait SortableRelation
{
    /**
     * @var array The array of all sortable relations with their sort_order pivot column.
     *
     * public $sortableRelations = ['related_model' => 'sort_order'];
     */

    /**
     * Boot the SortableRelation trait for this model.
     * Make sure to add the sort_order value if a related model has been attached.
     * @return void
     */
    public function initializeSortableRelation()
    {
        $this->bindEvent('model.relation.afterAttach', function ($relationName, $attached, $data) {
            if (array_key_exists($relationName, $this->getSortableRelations())) {
                $column = $this->getRelationSortOrderColumn($relationName);
                $relation = $this->$relationName();
                $order = $relation->max($column);

                foreach ($attached as $id) {
                    $order++;
                    $this->updateRelationOrder($relation, $id, $column, $order);
                }
            }
        });

        // Make sure all defined sortable relations load the sort_order column as pivot data.
        foreach ($this->getSortableRelations() as $relationName => $column) {
            $relation = $this->$relationName();
            if (method_exists($relation, 'updateExistingPivot')) {
                $definition = $this->getRelationDefinition($relationName);
                $pivot = array_wrap(array_get($definition, 'pivot', []));

                if (!in_array($column, $pivot)) {
                    $pivot[] = $column;
                    $definition['pivot'] = $pivot;

                    $relationType = $this->getRelationType($relationName);
                    $this->$relationType[$relationName] = $definition;
                }
            }
        }
    }

    /**
     * Sets the sort order of records to the specified orders. If the orders is
     * undefined, the record identifier is used.
     * @param  string $relation
     * @param  mixed  $itemIds
     * @param  array  $itemOrders
     * @return void
     */
    public function setRelationOrder($relationName, $itemIds, $itemOrders = null)
    {
        if (!is_array($itemIds)) {
            $itemIds = [$itemIds];
        }

        if ($itemOrders === null) {
            $itemOrders = $itemIds;
        }

        if (count($itemIds) != count($itemOrders)) {
            throw new Exception('Invalid setRelationOrder call - count of itemIds do not match count of itemOrders');
        }

        $column = $this->getRelationSortOrderColumn($relationName);

        foreach ($itemIds as $index => $id) {
            $relation = $this->$relationName();
            $order = $itemOrders[$index];
            $this->updateRelationOrder($relation, $id, $column, $order);
        }
    }

    public function updateRelationOrder($relation, $id, $column, $order)
    {
        if (method_exists($relation, 'updateExistingPivot')) {
            $relation->updateExistingPivot($id, [ $column => (int)$order ]);
        } else {
            $record = $relation->find($id);
            $record->sort_order = $order;
            $record->save();
        }
    }

    /**
     * Get the name of the "sort_order" column.
     * @param string $relation
     * @return string
     */
    public function getRelationSortOrderColumn($relation)
    {
        return $this->getSortableRelations()[$relation] ?? 'sort_order';
    }

    /**
     * Returns all configured sortable relations.
     * @return array
     */
    protected function getSortableRelations()
    {
        if (property_exists($this, 'sortableRelations')) {
            return $this->sortableRelations;
        }
        return [];
    }
}
