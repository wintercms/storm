<?php namespace Winter\Storm\Database\Traits;

use Exception;

use Winter\Sorm\Database\Model;

/**
 * HasSortableRelations trait
 *
 * Usage:
 *
 * In the model class definition add:
 *
 *   use \Winter\Storm\Database\Traits\HasSortableRelations;
 *
 *   public $sortableRelations = ['relation_name' => 'sort_order_column'];
 *
 * To set orders:
 *
 *   $model->setSortableRelationOrder($relationName, $recordIds, $recordOrders);
 *
 */
trait HasSortableRelations
{
    /**
     * @var array The array of all sortable relations with their sort_order pivot column.
     *
     * public $sortableRelations = ['related_model' => 'sort_order'];
     */

    /**
     * Initialize the HasSortableRelations trait for this model.
     * Sets the sort_order value if a related model has been attached.
     */
    public function initializeHasSortableRelations() : void
    {
        $sortableRelations = $this->getSortableRelations();

        $this->bindEvent('model.relation.afterAttach', function ($relationName, $attached, $data) use ($sortableRelations) {
            // Only for pivot-based relations
            if (array_key_exists($relationName, $sortableRelations)) {
                $column = $this->getRelationSortOrderColumn($relationName);

                foreach ($attached as $id) {
                    $this->updateRelationOrder($relationName, $id, $column);
                }
            }
        });

        $this->bindEvent('model.relation.afterAdd', function ($relationName, $relatedModel) use ($sortableRelations) {
            // Only for non pivot-based relations
            if (array_key_exists($relationName, $sortableRelations)) {
                $column = $this->getRelationSortOrderColumn($relationName);

                $this->updateRelationOrder($relationName, $relatedModel->getKey(), $column);
            }
        });

        foreach ($sortableRelations as $relationName => $column) {
            $relation = $this->$relationName();
            if (method_exists($relation, 'updateExistingPivot')) {
                // Make sure all pivot-based defined sortable relations load the sort_order column as pivot data.
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
    protected function updateRelationOrder(string $relationName, string|int|Model $id, string $column, int $order = 0) : void
    {
        $relation = $this->{$relationName}();

        if (!$order) {
            $order = $relation->count();
        }
        if (method_exists($relation, 'updateExistingPivot')) {
            $relation->updateExistingPivot($id, [ $column => (int)$order ]);
        } else {
            if ($id instanceof Model) {
                $record = $id;
            } else {
                $record = $relation->find($id);
            }
            $record->{$column} = (int)$order;
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
