<?php namespace Winter\Storm\Database\Traits;

use Exception;

use Winter\Storm\Database\Model;
use Winter\Storm\Database\Models\DeferredBinding;

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
 *   $model->setRelationOrder($relationName, $recordIds, $recordOrders);
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

                // If the records were attached with an explicit sort order (e.g. when committing
                // deferred bindings that already carry a pivot sort order), keep it - otherwise
                // auto-append the records to the end of the relation. $data is the list of pivot
                // insert rows, one per attached record.
                if (is_array($data)) {
                    foreach ($data as $row) {
                        if (is_array($row) && isset($row[$column])) {
                            return;
                        }
                    }
                }

                foreach ($attached as $id) {
                    $this->updateRelationOrder($relationName, $id, $column, null);
                }
            }
        });

        $this->bindEvent('model.relation.afterAdd', function ($relationName, $relatedModel) use ($sortableRelations) {
            // Only for non pivot-based relations
            if (array_key_exists($relationName, $sortableRelations)) {
                $column = $this->getRelationSortOrderColumn($relationName);

                // No order - auto-append to the end of the relation.
                $this->updateRelationOrder($relationName, $relatedModel->getKey(), $column, null);
            }
        });

        foreach ($sortableRelations as $relationName => $column) {
            $relationType = $this->getRelationType($relationName);
            if (!in_array($relationType, ['belongsToMany', 'morphToMany', 'morphedByMany'])) {
                continue;
            }
            $definition = $this->getRelationDefinition($relationName);

            // Make sure the sort order column is available as pivot data.
            $pivot = array_wrap(array_get($definition, 'pivot', []));
            if (!in_array($column, $pivot)) {
                $pivot[] = $column;
                $definition['pivot'] = $pivot;
            }

            // Auto-add an ordering clause so the relation is returned in sort order by default.
            // Qualify with the pivot table name to avoid colliding with a column of the same
            // name on the related model (which would silently order by the wrong column).
            if (!array_key_exists('order', $definition)) {
                $pivotTable = array_get($definition, 'table');
                $definition['order'] = ($pivotTable ? $pivotTable . '.' : '') . $column . ' asc';
            }

            $this->$relationType[$relationName] = $definition;
        }
    }

    /**
     * Set the sort order of records to the specified orders. If the orders are
     * undefined, a sequential 1..N order is assigned in the given id order.
     *
     * When a $sessionKey is provided the relation is operating in deferred mode:
     * the sort order is written to the pivot_data of the matching deferred_bindings
     * records instead of the pivot table. The caller (e.g. RelationController) is
     * responsible for passing the sessionKey only when in deferred mode.
     */
    public function setRelationOrder(
        string $relationName,
        string|int|array $itemIds,
        array $itemOrders = [],
        ?string $sessionKey = null
    ) : void
    {
        if (!is_array($itemIds)) {
            $itemIds = [$itemIds];
        }

        if (empty($itemIds)) {
            return;
        }

        if (empty($itemOrders)) {
            $itemOrders = range(1, count($itemIds));
        }

        if (count($itemIds) != count($itemOrders)) {
            throw new Exception('Invalid setRelationOrder call - count of itemIds do not match count of itemOrders');
        }

        $column = $this->getRelationSortOrderColumn($relationName);

        /*
         * Deferred mode - update pivot_data on the deferred_bindings records.
         * Batch the lookup into a single query, then save each binding (each row
         * carries its own JSON pivot_data so individual saves are required).
         */
        if ($sessionKey) {
            $bindings = DeferredBinding::where('master_type', get_class($this))
                ->where('master_field', $relationName)
                ->whereIn('slave_id', $itemIds)
                ->where('session_key', $sessionKey)
                ->where('is_bind', 1)
                ->get()
                ->keyBy('slave_id');

            foreach ($itemIds as $index => $id) {
                if ($binding = $bindings->get($id)) {
                    $pivotData = $binding->pivot_data ?: [];
                    $pivotData[$column] = (int) $itemOrders[$index];
                    $binding->pivot_data = $pivotData;
                    $binding->save();
                }
            }

            return;
        }

        foreach ($itemIds as $index => $id) {
            // Pass the explicit order (which may legitimately be 0) so it is not mistaken
            // for the "auto-append" case.
            $this->updateRelationOrder($relationName, $id, $column, (int) $itemOrders[$index]);
        }
    }

    /**
     * Update relation record sort_order. A null $order auto-appends the record to the end
     * of the relation; an explicit integer (including 0) is written as-is.
     */
    protected function updateRelationOrder(string $relationName, string|int|Model $id, string $column, ?int $order = null) : void
    {
        $relation = $this->{$relationName}();

        if ($order === null) {
            // Append to the end of the relation. For pivot-based relations use the pivot
            // query directly so a defined "order" clause on the relation cannot interfere
            // with the aggregate, and so non-contiguous sort orders are handled correctly.
            if (method_exists($relation, 'newPivotQuery')) {
                $order = ((int) $relation->newPivotQuery()->max($column)) + 1;
            } else {
                $order = $relation->count() + 1;
            }
        }
        if (method_exists($relation, 'updateExistingPivot')) {
            $relation->updateExistingPivot($id, [ $column => (int)$order ]);
        } else {
            if ($id instanceof Model) {
                $record = $id;
            } else {
                $record = $relation->getRelated()->find($id);
            }
            $record->{$column} = (int)$order;
            $record->save();
        }
    }

    /**
     * Returns true if the given relation is configured as a sortable relation.
     */
    public function isSortableRelation(string $relationName) : bool
    {
        return array_key_exists($relationName, $this->getSortableRelations());
    }

    /**
     * Get the name of the "sort_order" column for the given relation.
     */
    public function getRelationSortOrderColumn(string $relationName) : string
    {
        return $this->getSortableRelations()[$relationName] ?? 'sort_order';
    }

    /**
     * Return all configured sortable relations.
     */
    public function getSortableRelations() : array
    {
        if (property_exists($this, 'sortableRelations')) {
            return $this->sortableRelations;
        }
        return [];
    }
}
