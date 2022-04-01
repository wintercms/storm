<?php namespace Winter\Storm\Database\Traits;

use Exception;

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
     */
    public function initializeSortableRelation() : void
    {
        $this->bindEvent('model.relation.afterAttach', function ($relationName, $attached, $data) {
            // won't work for HasMany relations as this event is only fired for pivot based relations
            if (array_key_exists($relationName, $this->getSortableRelations())) {
                $column = $this->getRelationSortOrderColumn($relationName);

                foreach ($attached as $id) {
                    $this->updateRelationOrder($relationName, $id, $column);
                }
            }
        });

        // Make sure all defined sortable relations load the sort_order column as pivot data.
        foreach ($this->getSortableRelations() as $relationName => $column) {
            $relation = $this->$relationName();
            // only for pivot based relations
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
     * Set the sort order of records to the specified orders. If the orders is
     * undefined, the record identifier is used.
     */
    public function setRelationOrder(string $relationName, string|int|array $itemIds, array $itemOrders = []) : void
    {
        if (!is_array($itemIds)) {
            $itemIds = [$itemIds];
        }

        if (empty($itemOrders)) {
            $itemOrders = $itemIds;
        }

        if (count($itemIds) != count($itemOrders)) {
            throw new Exception('Invalid setRelationOrder call - count of itemIds do not match count of itemOrders');
        }

        $column = $this->getRelationSortOrderColumn($relationName);

        foreach ($itemIds as $index => $id) {
            $order = (int)$itemOrders[$index];
            $this->updateRelationOrder($relationName, $id, $column, $order);
        }
    }

    /**
     * Update relation record sort_order.
     */
    protected function updateRelationOrder(string $relationName, int $id, string $column, int $order = 0) : void
    {
        $relation = $this->$relationName();

        if (!$order) {
            $order = $relation->max($column) + 1;
        }
        if (method_exists($relation, 'updateExistingPivot')) {
            $relation->updateExistingPivot($id, [ $column => (int)$order ]);
        } else {
            $record = $relation->find($id);
            $record->sort_order = (int)$order;
            $record->save();
        }
    }

    /**
     * Get the name of the "sort_order" column.
     */
    public function getRelationSortOrderColumn(string $relationName) : string
    {
        return $this->getSortableRelations()[$relationName] ?? 'sort_order';
    }

    /**
     * Return all configured sortable relations.
     */
    protected function getSortableRelations() : array
    {
        if (property_exists($this, 'sortableRelations')) {
            return $this->sortableRelations;
        }
        return [];
    }
}
