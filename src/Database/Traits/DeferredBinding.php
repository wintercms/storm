<?php namespace Winter\Storm\Database\Traits;

use Winter\Storm\Database\Collection;
use Illuminate\Database\Eloquent\Model;
use Winter\Storm\Database\Models\DeferredBinding as DeferredBindingModel;

trait DeferredBinding
{
    /**
     * @var string A unique session key used for deferred binding.
     */
    public $sessionKey;

    /**
     * Returns true if a relation exists and can be deferred.
     */
    public function isDeferrable(string $relationName): bool
    {
        if (!$this->hasRelation($relationName)) {
            return false;
        }

        return in_array(
            $this->getRelationType($relationName),
            $this->getDeferrableRelationTypes()
        );
    }

    /**
     * Bind a deferred relationship to the supplied record.
     */
    public function bindDeferred(string $relation, Model $record, string $sessionKey, array $pivotData = []): DeferredBindingModel
    {
        $binding = new DeferredBindingModel;
        $binding->setConnection($this->getConnectionName());
        $binding->master_type = get_class($this);
        $binding->master_field = $relation;
        $binding->slave_type = get_class($record);
        $binding->slave_id = $record->getKey();
        $binding->pivot_data = $pivotData;
        $binding->session_key = $sessionKey;
        $binding->is_bind = true;
        $binding->save();
        return $binding;
    }

    /**
     * Unbind a deferred relationship to the supplied record.
     */
    public function unbindDeferred(string $relation, Model $record, string $sessionKey): DeferredBindingModel
    {
        $binding = new DeferredBindingModel;
        $binding->setConnection($this->getConnectionName());
        $binding->master_type = get_class($this);
        $binding->master_field = $relation;
        $binding->slave_type = get_class($record);
        $binding->slave_id = $record->getKey();
        $binding->session_key = $sessionKey;
        $binding->is_bind = false;
        $binding->save();
        return $binding;
    }

    /**
     * Cancel all deferred bindings to this model.
     */
    public function cancelDeferred(string $sessionKey): void
    {
        DeferredBindingModel::cancelDeferredActions(get_class($this), $sessionKey);
    }

    /**
     * Commit all deferred bindings to this model.
     */
    public function commitDeferred(string $sessionKey): void
    {
        $this->commitDeferredOfType($sessionKey);
        DeferredBindingModel::flushDuplicateCache();
    }

    /**
     * Internally used method to commit all deferred bindings before saving.
     * It is a rare need to have to call this, since it only applies to the
     * "belongs to" relationship which generally does not need deferring.
     */
    protected function commitDeferredBefore(string $sessionKey): void
    {
        $this->commitDeferredOfType($sessionKey, 'belongsTo');
    }

    /**
     * Internally used method to commit all deferred bindings after saving.
     */
    protected function commitDeferredAfter(string $sessionKey): void
    {
        $this->commitDeferredOfType($sessionKey, null, 'belongsTo');
        DeferredBindingModel::flushDuplicateCache();
    }

    /**
     * Internal method for committing deferred relations.
     */
    protected function commitDeferredOfType(string $sessionKey, string|array|null $include = null, string|array|null $exclude = null): void
    {
        if (!strlen($sessionKey)) {
            return;
        }

        $bindings = $this->getDeferredBindingRecords($sessionKey);

        foreach ($bindings as $binding) {
            if (!($relationName = $binding->master_field)) {
                continue;
            }

            if (!$this->hasRelation($relationName)) {
                continue;
            }

            $relationType = $this->getRelationType($relationName);
            $allowedTypes = $this->getDeferrableRelationTypes();

            if ($include) {
                $allowedTypes = array_intersect($allowedTypes, (array) $include);
            } elseif ($exclude) {
                $allowedTypes = array_diff($allowedTypes, (array) $exclude);
            }

            if (!in_array($relationType, $allowedTypes)) {
                continue;
            }

            /*
             * Find the slave model
             */
            $slaveModel = $this->makeRelation($relationName) ?: new $binding->slave_type;
            $slaveModel = $slaveModel->find($binding->slave_id);

            if (!$slaveModel) {
                continue;
            }

            /*
             * Bind/Unbind the relationship, save the related model with any
             * deferred bindings it might have and delete the binding action
             */
            $relationObj = $this->$relationName();

            if ($binding->is_bind) {
                if (in_array($relationType, ['belongsToMany', 'morphToMany', 'morphedByMany'])) {
                    $relationObj->add($slaveModel, null, (array) $binding->pivot_data);
                } else {
                    $relationObj->add($slaveModel);
                }
            } else {
                $relationObj->remove($slaveModel);
            }

            $binding->delete();
        }
    }

    /**
     * Returns any outstanding binding records for this model.
     */
    protected function getDeferredBindingRecords(string $sessionKey): Collection
    {
        $binding = new DeferredBindingModel;

        $binding->setConnection($this->getConnectionName());

        return $binding
            ->where('master_type', get_class($this))
            ->where('session_key', $sessionKey)
            ->get()
        ;
    }

    /**
     * Returns all possible relation types that can be deferred.
     */
    protected function getDeferrableRelationTypes(): array
    {
        return [
            'hasMany',
            'hasOne',
            'morphMany',
            'morphToMany',
            'morphedByMany',
            'morphOne',
            'attachMany',
            'attachOne',
            'belongsToMany',
            'belongsTo'
        ];
    }
}
