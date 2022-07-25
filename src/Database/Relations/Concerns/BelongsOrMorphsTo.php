<?php namespace Winter\Storm\Database\Relations\Concerns;

use Illuminate\Database\Eloquent\Model;

trait BelongsOrMorphsTo
{
    /**
     * Associate the model instance to the given parent.
     */
    public function associate(Model $model): Model
    {
        /**
         * @event model.relation.beforeAssociate
         * Called before associating a relation to the model (only for BelongsTo/MorphTo relations)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.beforeAssociate', function (string $relationName, \Winter\Storm\Database\Model $relatedModel) use (\Winter\Storm\Database\Model $model) {
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
         *     $model->bindEvent('model.relation.afterAssociate', function (string $relationName, \Winter\Storm\Database\Model $relatedModel) use (\Winter\Storm\Database\Model $model) {
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
     * Dissociate previously associated model from the given parent.
     */
    public function dissociate(): Model
    {
        /**
         * @event model.relation.beforeDissociate
         * Called before dissociating a relation to the model (only for BelongsTo/MorphTo relations)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.beforeDissociate', function (string $relationName) use (\Winter\Storm\Database\Model $model) {
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
         *     $model->bindEvent('model.relation.afterDissociate', function (string $relationName) use (\Winter\Storm\Database\Model $model) {
         *         $modelClass = get_class($model);
         *         traceLog("{$relationName} was dissociated from {$modelClass}.");
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.afterDissociate', [$this->relationName]);

        return $result;
    }
}
