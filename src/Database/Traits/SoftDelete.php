<?php

namespace Winter\Storm\Database\Traits;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as CollectionBase;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Winter\Storm\Database\Relations\AttachMany;
use Winter\Storm\Database\Relations\AttachOne;
use Winter\Storm\Database\Relations\BelongsToMany;
use Winter\Storm\Database\Relations\HasMany;
use Winter\Storm\Database\Relations\HasOne;
use Winter\Storm\Database\Relations\MorphMany;
use Winter\Storm\Database\Relations\MorphOne;
use Winter\Storm\Database\Relations\MorphToMany;

/**
 * @mixin \Winter\Storm\Database\Model
 */
trait SoftDelete
{
    /**
     * Indicates if the model is currently force deleting.
     *
     * @var bool
     */
    protected $forceDeleting = false;

    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootSoftDelete()
    {
        static::addGlobalScope(new SoftDeletingScope);

        static::restoring(function ($model) {
            if ($model->methodExists('beforeRestore')) {
                // Register the method as a listener with default priority
                // to allow for complete control over the execution order
                $model->bindEvent('model.beforeRestore', [$model, 'beforeRestore']);
            }
            /**
             * @event model.beforeRestore
             * Called before the model is restored from a soft delete
             *
             * Example usage:
             *
             *     $model->bindEvent('model.beforeRestore', function () use (\Winter\Storm\Database\Model $model) {
             *         \Log::info("{$model->name} is going to be restored!");
             *     });
             *
             */
            return $model->fireEvent('model.beforeRestore', halt: true);
        });

        static::restored(function ($model) {
            if ($model->methodExists('afterRestore')) {
                // Register the method as a listener with default priority
                // to allow for complete control over the execution order
                $model->bindEvent('model.afterRestore', [$model, 'afterRestore']);
            }
            /**
             * @event model.afterRestore
             * Called after the model is restored from a soft delete
             *
             * Example usage:
             *
             *     $model->bindEvent('model.afterRestore', function () use (\Winter\Storm\Database\Model $model) {
             *         \Log::info("{$model->name} has been brought back to life!");
             *     });
             *
             */
            return $model->fireEvent('model.afterRestore', halt: true);
        });

        foreach ([
            AttachMany::class,
            AttachOne::class,
            BelongsToMany::class,
            HasMany::class,
            HasOne::class,
            MorphMany::class,
            MorphOne::class,
            MorphToMany::class,
        ] as $relationClass) {
            $relationClass::extend(function () {
                // Prevent double-defining the dynamically added properties and methods below
                if ($this->methodExists('softDeletable')) {
                    return;
                }

                $this->addDynamicProperty('isSoftDeletable', false);
                $this->addDynamicProperty('deletedAtColumn', 'deleted_at');

                $this->addDynamicMethod('softDeletable', function (string $deletedAtColumn = 'deleted_at') {
                    $this->isSoftDeletable = true;
                    $this->deletedAtColumn = $deletedAtColumn;
                    return $this;
                });

                $this->addDynamicMethod('notSoftDeletable', function () {
                    $this->isSoftDeletable = false;
                    return $this;
                });

                $this->addDynamicMethod('isSoftDeletable', function () {
                    return $this->isSoftDeletable;
                });

                $this->addDynamicMethod('getDeletedAtColumn', function () {
                    return $this->deletedAtColumn;
                });
            }, true);
        }
    }

    /**
     * Helper method to check if the model is currently
     * being hard or soft deleted, useful in events.
     *
     * @return bool
     */
    public function isSoftDelete()
    {
        return !$this->forceDeleting;
    }

    /**
     * Force a hard delete on a soft deleted model.
     *
     * @return void
     */
    public function forceDelete()
    {
        $this->forceDeleting = true;

        $this->delete();

        $this->forceDeleting = false;
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return mixed
     */
    protected function performDeleteOnModel()
    {
        if ($this->forceDeleting) {
            $this->performDeleteOnRelations();
            return $this->withTrashed()->where($this->getKeyName(), $this->getKey())->forceDelete();
        }

        $this->performSoftDeleteOnRelations();
        return $this->runSoftDelete();
    }

    /**
     * Locates relations with softDelete flag and cascades the delete event.
     *
     * @return void
     */
    protected function performSoftDeleteOnRelations()
    {
        foreach ($this->getDefinedRelations() as $name => $relation) {
            if (!$relation->methodExists('isSoftDeletable')) {
                continue;
            }

            // Apply soft delete to the relation if it's defined in the array config
            $definition = $this->getRelationDefinition($name);
            if (array_get($definition, 'softDelete', false)) {
                $relation->softDeletable($definition['deletedAtColumn'] ?? 'deleted_at');
            }

            if (!$relation->isSoftDeletable()) {
                continue;
            }

            if (in_array(get_class($relation), [BelongsToMany::class, MorphToMany::class])) {
                // relations using pivot table
                $value = $this->fromDateTime($this->freshTimestamp());
                $this->updatePivotDeletedAtColumn($relation, $value);
                return;
            }

            $records = $relation->getResults();

            if ($records instanceof EloquentModel) {
                $records->delete();
            } elseif ($records instanceof CollectionBase) {
                $records->each(function ($model) {
                    $model->delete();
                });
            }
        }
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function runSoftDelete()
    {
        $query = $this->newQuery()->where($this->getKeyName(), $this->getKey());

        $this->{$this->getDeletedAtColumn()} = $time = $this->freshTimestamp();

        $query->update(array($this->getDeletedAtColumn() => $this->fromDateTime($time)));
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @return bool|null
     */
    public function restore()
    {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->performRestoreOnRelations();

        $this->{$this->getDeletedAtColumn()} = null;

        // Once we have saved the model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }

    /**
     * Update relation pivot table deleted_at column
     */
    protected function updatePivotDeletedAtColumn(Relation $relation, $value)
    {
        $relation->newPivotQuery()->update([
            $relation->getDeletedAtColumn() => $value,
        ]);
    }

    /**
     * Locates relations with softDelete flag and cascades the restore event.
     *
     * @return void
     */
    protected function performRestoreOnRelations()
    {
        foreach ($this->getDefinedRelations() as $name => $relation) {
            if (!$relation->methodExists('isSoftDeletable')) {
                continue;
            }

            // Apply soft delete to the relation if it's defined in the array config
            $definition = $this->getRelationDefinition($name);
            if (array_get($definition, 'softDelete', false)) {
                $relation->softDeletable($definition['deletedAtColumn'] ?? 'deleted_at');
            }

            if (!$relation->isSoftDeletable()) {
                continue;
            }

            if (in_array(get_class($relation), [BelongsToMany::class, MorphToMany::class])) {
                $this->updatePivotDeletedAtColumn($relation, null);
                return;
            }

            $results = $relation->onlyTrashed()->getResults();
            if (!$results) {
                continue;
            }

            if ($results instanceof EloquentModel) {
                $results->restore();
            } elseif ($results instanceof CollectionBase) {
                $results->each(function ($model) {
                    $model->restore();
                });
            }
        }
    }

    /**
     * Determine if the model instance has been soft-deleted.
     *
     * @return bool
     */
    public function trashed()
    {
        return !is_null($this->{$this->getDeletedAtColumn()});
    }

    /**
     * Get a new query builder that includes soft deletes.
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function withTrashed()
    {
        return with(new static)->newQueryWithoutScope(new SoftDeletingScope);
    }

    /**
     * Get a new query builder that only includes soft deletes.
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function onlyTrashed()
    {
        $instance = new static;

        $column = $instance->getQualifiedDeletedAtColumn();

        return $instance->newQueryWithoutScope(new SoftDeletingScope)->whereNotNull($column);
    }

    /**
     * Register a restoring model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restoring($callback)
    {
        static::registerModelEvent('restoring', $callback);
    }

    /**
     * Register a restored model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restored($callback)
    {
        static::registerModelEvent('restored', $callback);
    }

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getDeletedAtColumn()
    {
        return defined('static::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
    }

    /**
     * Get the fully qualified "deleted at" column.
     *
     * @return string
     */
    public function getQualifiedDeletedAtColumn()
    {
        return $this->getTable().'.'.$this->getDeletedAtColumn();
    }
}
