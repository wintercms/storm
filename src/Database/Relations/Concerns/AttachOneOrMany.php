<?php namespace Winter\Storm\Database\Relations\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Winter\Storm\Support\Facades\DbDongle;
use Winter\Storm\Database\Attach\File as FileModel;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Winter\Storm\Database\Relations\AttachOne;

trait AttachOneOrMany
{
    use DeferOneOrMany;

    /**
     * @var ?boolean Default value for file public or protected state.
     */
    protected $public;

    /**
     * The field name (relation) to associate this attachment with.
     */
    protected string $fieldName;

    /**
     * Determines if the file should be flagged "public" or not.
     */
    public function isPublic()
    {
        if (isset($this->public)) {
            return $this->public;
        }

        return true;
    }

    /**
     * Set the field (relation name) constraint on the query.
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $this->query->where($this->morphType, $this->morphClass);

            $this->query->where($this->foreignKey, '=', $this->getParentKey());

            $this->query->where('field', $this->getFieldName());

            $this->query->whereNotNull($this->foreignKey);
        }
    }

    /**
     * Get the field name (relation) to associate this attachment with.
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Add the constraints for a relationship count query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        if ($parentQuery->getQuery()->from == $query->getQuery()->from) {
            $query = $this->getRelationExistenceQueryForSelfJoin($query, $parentQuery, $columns);
        }
        else {
            $key = DbDongle::cast($this->getQualifiedParentKeyName(), 'TEXT');

            $query = $query->select($columns)->whereColumn($this->getExistenceCompareKey(), '=', $key);
        }

        $query = $query->where($this->morphType, $this->morphClass);

        return $query->where('field', $this->fieldName);
    }

    /**
     * Add the constraints for a relationship query on the same table.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQueryForSelfRelation(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $query->select($columns)->from(
            $query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash()
        );

        $query->getModel()->setTable($hash);

        $key = DbDongle::cast($this->getQualifiedParentKeyName(), 'TEXT');

        return $query->whereColumn($hash.'.'.$this->getForeignKeyName(), '=', $key);
    }

    /**
     * Set the field constraint for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        parent::addEagerConstraints($models);

        $this->query->where('field', $this->fieldName);
    }

    /**
     * Save the supplied related model.
     */
    public function save(Model $model, $sessionKey = null)
    {
        // Delete siblings for single attachments
        if ($sessionKey === null && $this instanceof AttachOne) {
            $this->delete();
        }

        if (!array_key_exists('is_public', $model->getAttributes())) {
            $model->setAttribute('is_public', $this->isPublic());
        }

        $model->setAttribute('field', $this->fieldName);

        if ($sessionKey === null) {
            return parent::save($model);
        }

        $this->add($model, $sessionKey);
        return $model->save() ? $model : false;
    }

    /**
     * Create a new instance of this related model.
     */
    public function create(array $attributes = [], $sessionKey = null)
    {
        // Delete siblings for single attachments
        if ($sessionKey === null && $this instanceof AttachOne) {
            $this->delete();
        }

        if (!array_key_exists('is_public', $attributes)) {
            $attributes = array_merge(['is_public' => $this->isPublic()], $attributes);
        }

        $attributes['field'] = $this->fieldName;

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
        if (!array_key_exists('is_public', $model->getAttributes())) {
            $model->setAttribute('is_public', $this->isPublic());
        }

        if ($sessionKey === null) {
            /**
             * @event model.relation.beforeAdd
             * Called before adding a relation to the model (for AttachOneOrMany, HasOneOrMany & MorphOneOrMany relations)
             *
             * Example usage:
             *
             *     $model->bindEvent('model.relation.beforeAdd', function (string $relationName, \Winter\Storm\Database\Model $relatedModel) use (\Winter\Storm\Database\Model $model) {
             *         if ($relationName === 'dummyRelation') {
             *             throw new \Exception("Invalid relation!");
             *         }
             *     });
             *
             */
            $this->parent->fireEvent('model.relation.beforeAdd', [$this->relationName, $model]);

            // Delete siblings for single attachments
            if ($this instanceof AttachOne) {
                $this->delete();
            }

            $model->setAttribute($this->getForeignKeyName(), $this->parent->getKey());
            $model->setAttribute($this->getMorphType(), $this->morphClass);
            $model->setAttribute('field', $this->fieldName);
            $model->save();

            /*
             * Use the opportunity to set the relation in memory
             */
            if ($this instanceof AttachOne) {
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
             *     $model->bindEvent('model.relation.afterAdd', function (string $relationName, \Winter\Storm\Database\Model $relatedModel) use (\Winter\Storm\Database\Model $model) {
             *         $relatedClass = get_class($relatedModel);
             *         $modelClass = get_class($model);
             *         traceLog("{$relatedClass} was added as {$relationName} to {$modelClass}.");
             *     });
             *
             */
            $this->parent->fireEvent('model.relation.afterAdd', [$this->relationName, $model]);
        } else {
            $this->parent->bindDeferred($this->relationName, $model, $sessionKey);
        }
    }

    /**
     * Attach an array of models to the parent instance with deferred binding support.
     * @param  array  $models
     * @return void
     */
    public function addMany($models, $sessionKey = null)
    {
        foreach ($models as $model) {
            $this->add($model, $sessionKey);
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
             *     $model->bindEvent('model.relation.beforeRemove', function (string $relationName, \Winter\Storm\Database\Model $relatedModel) use (\Winter\Storm\Database\Model $model) {
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
                $model->setAttribute('field', null);
                $model->save();
            }

            /*
             * Use the opportunity to set the relation in memory
             */
            if ($this instanceof AttachOne) {
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
             *     $model->bindEvent('model.relation.afterRemove', function (string $relationName, \Winter\Storm\Database\Model $relatedModel) use (\Winter\Storm\Database\Model $model) {
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

    /**
     * Returns true if the specified value can be used as the data attribute.
     */
    protected function isValidFileData($value)
    {
        if ($value instanceof UploadedFile) {
            return true;
        }

        if (is_string($value) && file_exists($value)) {
            return true;
        }

        return false;
    }

    /**
     * Creates a file object suitable for validation, called from
     * the `getValidationValue` method. Value can be a file model,
     * UploadedFile object (expected) or potentially a string.
     *
     * @param mixed $value
     * @return UploadedFile
     */
    public function makeValidationFile($value)
    {
        if ($value instanceof FileModel) {
            return new UploadedFile(
                $value->getLocalPath(),
                $value->file_name,
                $value->content_type,
                null,
                true
            );
        }

        /*
         * @todo `$value` might be a string, may not validate
         */

        return $value;
    }

    /**
     * Get the foreign key for the relationship.
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Get the associated "other" key of the relationship.
     * @return string
     */
    public function getOtherKey()
    {
        return $this->localKey;
    }
}
