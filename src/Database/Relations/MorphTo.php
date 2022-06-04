<?php namespace Winter\Storm\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo as MorphToBase;

class MorphTo extends MorphToBase
{
    use DefinedConstraints;

    /**
     * @var string The "name" of the relationship.
     */
    protected $relationName;

    public function __construct(Builder $query, Model $parent, $foreignKey, $otherKey, $type, $relationName)
    {
        $this->relationName = $relationName;

        parent::__construct($query, $parent, $foreignKey, $otherKey, $type, $relationName);

        $this->addDefinedConstraints();
    }

    /**
     * Associate the model instance to the given parent.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function associate($model)
    {
        /**
         * @event model.relation.beforeAssociate
         * Called before associating a relation to the model (only for BelongsTo/MorphTo relations)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.beforeAssociate', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
         *         if ($relationName === 'dummyRelation') {
         *             throw new \Exception("Invalid relation!");
         *         }
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.beforeAssociate', [$this->relationName, $model]);

        $result = parent::associate($model);

        /**
         * @event model.relation.afterAssociate
         * Called after associating a relation to the model (only for BelongsTo/MorphTo relations)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.afterAssociate', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
         *         $relatedClass = get_class($relatedModel);
         *         $modelClass = get_class($model);
         *         traceLog("{$relatedClass} was associated as {$relationName} to {$modelClass}.");
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.afterAssociate', [$this->relationName, $model]);

        return $result;
    }

    /**
     * Dissociate previously dissociated model from the given parent.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function dissociate()
    {
        /**
         * @event model.relation.beforeDissociate
         * Called before dissociating a relation to the model (only for BelongsTo/MorphTo relations)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.beforeDissociate', function (string $relationName) use (\October\Rain\Database\Model $model) {
         *         if ($relationName === 'permanentRelation') {
         *             throw new \Exception("Cannot dissociate a permanent relation!");
         *         }
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.beforeDissociate', [$this->relationName]);

        $result = parent::dissociate();

        /**
         * @event model.relation.afterDissociate
         * Called after dissociating a relation to the model (only for BelongsTo/MorphTo relations)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.afterDissociate', function (string $relationName) use (\October\Rain\Database\Model $model) {
         *         $modelClass = get_class($model);
         *         traceLog("{$relationName} was dissociated from {$modelClass}.");
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.afterDissociate', [$this->relationName]);

        return $result;
    }

    /**
     * Helper for setting this relationship using various expected
     * values. For example, $model->relation = $value;
     */
    public function setSimpleValue($value)
    {
        // Nulling the relationship
        if (!$value) {
            $this->dissociate();
            return;
        }

        if ($value instanceof Model) {
            /*
             * Non existent model, use a single serve event to associate it again when ready
             */
            if (!$value->exists) {
                $value->bindEventOnce('model.afterSave', function () use ($value) {
                    $this->associate($value);
                });
            }

            $this->associate($value);
            $this->parent->setRelation($this->relationName, $value);
        }
        elseif (is_array($value)) {
            list($modelId, $modelClass) = $value;
            $this->parent->setAttribute($this->foreignKey, $modelId);
            $this->parent->setAttribute($this->morphType, $modelClass);
            $this->parent->reloadRelations($this->relationName);
        }
        else {
            $this->parent->setAttribute($this->foreignKey, $value);
            $this->parent->reloadRelations($this->relationName);
        }
    }

    /**
     * Helper for getting this relationship simple value,
     * generally useful with form values.
     */
    public function getSimpleValue()
    {
        return [
            $this->parent->getAttribute($this->foreignKey),
            $this->parent->getAttribute($this->morphType)
        ];
    }
}
