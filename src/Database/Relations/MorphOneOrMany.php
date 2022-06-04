<?php namespace Winter\Storm\Database\Relations;

use Illuminate\Support\Facades\Db;
use Illuminate\Database\Eloquent\Model;

trait MorphOneOrMany
{
    use DeferOneOrMany;

    /**
     * @var string The "name" of the relationship.
     */
    protected $relationName;

    /**
     * Save the supplied related model with deferred binding support.
     */
    public function save(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            return parent::save($model);
        }

        $this->add($model, $sessionKey);
        return $model->save() ? $model : false;
    }

    /**
     * Create a new instance of this related model with deferred binding support.
     */
    public function create(array $attributes = [], $sessionKey = null)
    {
        $model = parent::create($attributes);

        if ($sessionKey !== null) {
            $this->add($model, $sessionKey);
        }

        return $model;
    }

    /**
     * Adds a model to this relationship type.
     */
    public function add(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            /**
             * @event model.relation.beforeAdd
             * Called before adding a relation to the model (for AttachOneOrMany, HasOneOrMany & MorphOneOrMany relations)
             *
             * Example usage:
             *
             *     $model->bindEvent('model.relation.beforeAdd', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
             *         if ($relationName === 'dummyRelation') {
             *             throw new \Exception("Invalid relation!");
             *         }
             *     });
             *
             */
            $this->parent->fireEvent('model.relation.beforeAdd', [$this->relationName, $model]);

            $model->setAttribute($this->getForeignKeyName(), $this->getParentKey());
            $model->setAttribute($this->getMorphType(), $this->morphClass);
            $model->save();

            /*
             * Use the opportunity to set the relation in memory
             */
            if ($this instanceof MorphOne) {
                $this->parent->setRelation($this->relationName, $model);
            }
            else {
                $this->parent->reloadRelations($this->relationName);
            }

            /**
             * @event model.relation.afterAdd
             * Called after adding a relation to the model (for AttachOneOrMany, HasOneOrMany & MorphOneOrMany relations)
             *
             * Example usage:
             *
             *     $model->bindEvent('model.relation.afterAdd', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
             *         $relatedClass = get_class($relatedModel);
             *         $modelClass = get_class($model);
             *         traceLog("{$relatedClass} was added as {$relationName} to {$modelClass}.");
             *     });
             *
             */
            $this->parent->fireEvent('model.relation.afterAdd', [$this->relationName, $model]);
        }
        else {
            $this->parent->bindDeferred($this->relationName, $model, $sessionKey);
        }
    }

    /**
     * Removes a model from this relationship type.
     */
    public function remove(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            /**
             * @event model.relation.beforeRemove
             * Called before removing a relation to the model (for AttachOneOrMany, HasOneOrMany & MorphOneOrMany relations)
             *
             * Example usage:
             *
             *     $model->bindEvent('model.relation.beforeRemove', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
             *         if ($relationName === 'permanentRelation') {
             *             throw new \Exception("Cannot dissociate a permanent relation!");
             *         }
             *     });
             *
             */
            $this->parent->fireEvent('model.relation.beforeRemove', [$this->relationName, $model]);

            $options = $this->parent->getRelationDefinition($this->relationName);

            if (array_get($options, 'delete', false)) {
                $model->delete();
            }
            else {
                /*
                 * Make this model an orphan ;~(
                 */
                $model->setAttribute($this->getForeignKeyName(), null);
                $model->setAttribute($this->getMorphType(), null);
                $model->save();
            }

            /*
             * Use the opportunity to set the relation in memory
             */
            if ($this instanceof MorphOne) {
                $this->parent->setRelation($this->relationName, null);
            }
            else {
                $this->parent->reloadRelations($this->relationName);
            }

            /**
             * @event model.relation.afterRemove
             * Called after removing a relation to the model (for AttachOneOrMany, HasOneOrMany & MorphOneOrMany relations)
             *
             * Example usage:
             *
             *     $model->bindEvent('model.relation.afterRemove', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
             *         $relatedClass = get_class($relatedModel);
             *         $modelClass = get_class($model);
             *         traceLog("{$relatedClass} was removed from {$modelClass}.");
             *     });
             *
             */
            $this->parent->fireEvent('model.relation.afterRemove', [$this->relationName, $model]);
        }
        else {
            $this->parent->unbindDeferred($this->relationName, $model, $sessionKey);
        }
    }
}
