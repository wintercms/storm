<?php

namespace Winter\Storm\Database\Concerns;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as CollectionBase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Winter\Storm\Database\Attributes\Relation;
use Winter\Storm\Database\Relations\AttachMany;
use Winter\Storm\Database\Relations\AttachOne;
use Winter\Storm\Database\Relations\BelongsTo;
use Winter\Storm\Database\Relations\BelongsToMany;
use Winter\Storm\Database\Relations\HasMany;
use Winter\Storm\Database\Relations\HasManyThrough;
use Winter\Storm\Database\Relations\HasOne;
use Winter\Storm\Database\Relations\HasOneThrough;
use Winter\Storm\Database\Relations\MorphMany;
use Winter\Storm\Database\Relations\MorphOne;
use Winter\Storm\Database\Relations\MorphTo;
use Winter\Storm\Database\Relations\MorphToMany;
use Winter\Storm\Support\Arr;

/**
 * Model relationship methods.
 *
 * The following functionality handles custom relationship functionality for Winter CMS models, extending the base
 * Laravel Eloquent relationship functionality.
 *
 * @method \Winter\Storm\Database\Relations\HasOne hasOne(string $related, string|null $foreignKey = null, string|null $localKey = null)
 * @method \Winter\Storm\Database\Relations\HasOneThrough hasOneThrough(string $related, string $through, string|null $firstKey = null, string|null $secondKey = null, string|null $localKey = null, string|null $secondLocalKey = null)
 * @method \Winter\Storm\Database\Relations\MorphOne morphOne(string $related, string $name, string|null $type = null, string|null $id = null, string|null $localKey = null)
 * @method \Winter\Storm\Database\Relations\BelongsTo belongsTo(string $related, string|null $foreignKey = null, string|null $ownerKey = null, string|null $relation = null)
 * @method \Winter\Storm\Database\Relations\MorphTo morphTo(string|null $name = null, string|null $type = null, string|null $id = null, string|null $ownerKey = null)
 * @method \Winter\Storm\Database\Relations\HasMany hasMany(string $related, string|null $foreignKey = null, string|null $localKey = null)
 * @method \Winter\Storm\Database\Relations\HasManyThrough hasManyThrough(string $related, string $through, string|null $firstKey = null, string|null $secondKey = null, string|null $localKey = null, string|null $secondLocalKey = null)
 * @method \Winter\Storm\Database\Relations\MorphMany morphMany(string $related, string $name, string|null $type = null, string|null $id = null, string|null $localKey = null)
 * @method \Winter\Storm\Database\Relations\BelongsToMany belongsToMany(string $related, string|null $table = null, string|null $foreignPivotKey = null, string|null $relatedPivotKey = null, string|null $parentKey = null, string|null $relatedKey = null, string|null $relation = null)
 * @method \Winter\Storm\Database\Relations\MorphToMany morphToMany(string $related, string $name, string|null $table = null, string|null $foreignPivotKey = null, string|null $relatedPivotKey = null, string|null $parentKey = null, string|null $relatedKey = null, bool $inverse = false)
 * @method \Winter\Storm\Database\Relations\MorphToMany morphedByMany(string $related, string $name, string|null $table = null, string|null $foreignPivotKey = null, string|null $relatedPivotKey = null, string|null $parentKey = null, string|null $relatedKey = null)
 */
trait HasRelationships
{
    /**
     * Cleaner declaration of relationships.
     * Uses a similar approach to the relation methods used by Eloquent, but as separate properties
     * that make the class file less cluttered.
     *
     * It should be declared with keys as the relation name, and value being a mixed array.
     * The relation type $morphTo does not include a classname as the first value.
     *
     * Example:
     * class Order extends Model
     * {
     *     protected $hasMany = [
     *         'items' => 'Item'
     *     ];
     * }
     * @var array
     */
    public $hasMany = [];

    /**
     * protected $hasOne = [
     *     'owner' => ['User', 'key' => 'user_id']
     * ];
     */
    public $hasOne = [];

    /**
     * protected $belongsTo = [
     *     'parent' => ['Category', 'key' => 'parent_id']
     * ];
     */
    public $belongsTo = [];

    /**
     * protected $belongsToMany = [
     *     'groups' => ['Group', 'table'=> 'join_groups_users']
     * ];
     */
    public $belongsToMany = [];

    /**
     * protected $morphTo = [
     *     'pictures' => []
     * ];
     */
    public $morphTo = [];

    /**
     * protected $morphOne = [
     *     'log' => ['History', 'name' => 'user']
     * ];
     */
    public $morphOne = [];

    /**
     * protected $morphMany = [
     *     'log' => ['History', 'name' => 'user']
     * ];
     */
    public $morphMany = [];

    /**
     * protected $morphToMany = [
     *     'tag' => ['Tag', 'table' => 'tagables', 'name' => 'tagable']
     * ];
     */
    public $morphToMany = [];

    public $morphedByMany = [];

    /**
     * protected $attachOne = [
     *     'picture' => ['Winter\Storm\Database\Attach\File', 'public' => false]
     * ];
     */
    public $attachOne = [];

    /**
     * protected $attachMany = [
     *     'pictures' => ['Winter\Storm\Database\Attach\File', 'name'=> 'imageable']
     * ];
     */
    public $attachMany = [];

    /**
     * protected $hasManyThrough = [
     *     'posts' => ['Posts', 'through' => 'User']
     * ];
     */
    public $hasManyThrough = [];

    /**
     * protected $hasOneThrough = [
     *     'post' => ['Posts', 'through' => 'User']
     * ];
     */
    public $hasOneThrough = [];

    /**
     * @var array Excepted relationship types, used to cycle and verify relationships.
     */
    protected static $relationTypes = [
        'hasOne' => HasOne::class,
        'hasMany' => HasMany::class,
        'belongsTo' => BelongsTo::class,
        'belongsToMany' => BelongsToMany::class,
        'morphTo' => MorphTo::class,
        'morphOne' => MorphOne::class,
        'morphMany' => MorphMany::class,
        'morphToMany' => MorphToMany::class,
        'morphedByMany' => MorphToMany::class,
        'attachOne' => AttachOne::class,
        'attachMany' => AttachMany::class,
        'hasOneThrough' => HasOneThrough::class,
        'hasManyThrough' => HasManyThrough::class,
    ];

    /**
     * @var array<string, string> Stores relations that have resolved to Laravel-style relation objects.
     */
    protected static array $resolvedRelations = [];

    //
    // Relations
    //

    /**
     * Checks if model has a relationship by supplied name.
     */
    public function hasRelation(string $name): bool
    {
        return $this->getRelationDefinition($name) !== null;
    }

    /**
     * Gets the name and relation object of all defined relations on the model.
     *
     * This differs from the `getRelations()` method provided by Laravel, which only returns the loaded relations. It
     * also contains the Relation object as a value, rather than the result of the relation as per Laravel's
     * implementation.
     */
    public function getDefinedRelations(): array
    {
        $relations = [];

        foreach (array_keys(static::$relationTypes) as $type) {
            foreach (array_keys($this->getRelationTypeDefinitions($type)) as $name) {
                $relations[$name] = $this->handleRelation($name, false);
            }
        }

        foreach ($this->getRelationMethods() as $relation) {
            $relations[$relation] = $this->{$relation}();
        }

        return $relations;
    }

    /**
     * Returns relationship details from a supplied name.
     *
     * If the name resolves to a relation method, the method's returned relation object will be converted back to an
     * array definition.
     */
    public function getRelationDefinition(string $name): ?array
    {
        if (method_exists($this, $name) && $this->isRelationMethod($name)) {
            return $this->relationMethodDefinition($name);
        }

        if (($type = $this->getRelationType($name)) !== null) {
            return (array) $this->getRelationTypeDefinition($type, $name) + $this->getRelationDefaults($type);
        }

        return null;
    }

    /**
     * Returns all defined relations of given type.
     */
    public function getRelationTypeDefinitions(string $type): array
    {
        if (in_array($type, array_keys(static::$relationTypes))) {
            return array_map(function ($relation) {
                return (is_string($relation)) ? [$relation] : $relation;
            }, $this->{$type});
        }

        return [];
    }

    /**
     * Returns the given relation definition.
     *
     * If no relation exists by the given name and type, `null` will be returned.
     */
    public function getRelationTypeDefinition(string $type, string $name): array|null
    {
        $definitions = $this->getRelationTypeDefinitions($type);

        if (isset($definitions[$name])) {
            return $definitions[$name];
        }

        return null;
    }

    /**
     * Returns relationship details for all relations defined on this model.
     */
    public function getRelationDefinitions(): array
    {
        $result = [];

        foreach (array_keys(static::$relationTypes) as $type) {
            $result[$type] = $this->getRelationTypeDefinitions($type);

            /*
             * Apply default values for the relation type
             */
            if ($defaults = $this->getRelationDefaults($type)) {
                foreach ($result[$type] as $relation => $options) {
                    $result[$type][$relation] = (array) $options + $defaults;
                }
            }
        }

        return $result;
    }

    /**
     * Returns a relationship type based on a supplied name.
     */
    public function getRelationType(string $name): ?string
    {
        if (method_exists($this, $name)) {
            return array_search(get_class($this->{$name}()), static::$relationTypes) ?: null;
        }

        foreach (array_keys(static::$relationTypes) as $type) {
            if ($this->getRelationTypeDefinition($type, $name) !== null) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Returns a new instance of a related model
     */
    public function makeRelation(string $name): ?Model
    {
        $relationType = $this->getRelationType($name);
        $relation = $this->getRelationDefinition($name);

        if ($relationType == 'morphTo' || !isset($relation[0])) {
            return null;
        }

        $relationClass = $relation[0];
        return $this->newRelatedInstance($relationClass);
    }

    /**
     * Save relation on push.
     *
     * Determines whether the specified relation should be saved when `push()` is called on the model, instead of
     * `save()`. By default, this will be true.
     */
    public function isRelationPushable(string $name): bool
    {
        $definition = $this->getRelationDefinition($name);
        if (is_null($definition) || !array_key_exists('push', $definition)) {
            return true;
        }

        return (bool) $definition['push'];
    }

    /**
     * Returns default relation arguments for a given relation type.
     */
    protected function getRelationDefaults(string $type): array
    {
        switch ($type) {
            case 'attachOne':
            case 'attachMany':
                return ['order' => 'sort_order', 'delete' => true];

            default:
                return [];
        }
    }

    /**
     * Creates a Laravel relation object from a Winter relation definition array.
     *
     * Winter has traditionally used array properties in the model to configure relationships. This method converts
     * these to the applicable Laravel relation object.
     */
    protected function handleRelation(string $relationName, bool $addConstraints = true): EloquentRelation
    {
        $relationType = $this->getRelationType($relationName);
        $definition = $this->getRelationDefinition($relationName);
        $relatedClass = $definition[0] ?? null;

        if (!isset($relatedClass) && $relationType != 'morphTo') {
            throw new InvalidArgumentException(sprintf(
                "Relation '%s' on model '%s' must specify a related class name.",
                $relationName,
                get_called_class()
            ));
        }

        if (isset($relatedClass) && $relationType == 'morphTo') {
            throw new InvalidArgumentException(sprintf(
                "Relation '%s' on model '%s' is a morphTo relation and should not specify a related class name.",
                $relationName,
                get_called_class()
            ));
        }

        // Create relation object based on relation
        switch ($relationType) {
            case 'hasOne':
                $relation = $this->hasOne(
                    $relatedClass,
                    $definition['key'] ?? null,
                    $definition['otherKey'] ?? null,
                );
                break;
            case 'hasMany':
                $relation = $this->hasMany(
                    $relatedClass,
                    $definition['key'] ?? null,
                    $definition['otherKey'] ?? null,
                );
                break;
            case 'belongsTo':
                $relation = $this->belongsTo(
                    $relatedClass,
                    $definition['key'] ?? null,
                    $definition['otherKey'] ?? null,
                    $relationName,
                );
                break;
            case 'belongsToMany':
                $relation = $this->belongsToMany(
                    $relatedClass,
                    $definition['table'] ?? null,
                    $definition['key'] ?? null,
                    $definition['otherKey'] ?? null,
                    $definition['parentKey'] ?? null,
                    $definition['relatedKey'] ?? null,
                    $relationName,
                );
                if (isset($definition['pivotModel'])) {
                    $relation->using($definition['pivotModel']);
                }
                break;
            case 'morphTo':
                $relation = $this->morphTo(
                    $definition['name'] ?? $relationName,
                    $definition['type'] ?? null,
                    $definition['id'] ?? null,
                    $definition['otherKey'] ?? null,
                );
                break;
            case 'morphOne':
                $relation = $this->morphOne(
                    $relatedClass,
                    $definition['name'],
                    $definition['type'] ?? null,
                    $definition['id'] ?? null,
                    $definition['key'] ?? null,
                );
                break;
            case 'morphMany':
                $relation = $this->morphMany(
                    $relatedClass,
                    $definition['name'],
                    $definition['type'] ?? null,
                    $definition['id'] ?? null,
                    $definition['key'] ?? null,
                );
                break;
            case 'morphToMany':
                $relation = $this->morphToMany(
                    $relatedClass,
                    $definition['name'],
                    $definition['table'] ?? null,
                    $definition['key'] ?? null,
                    $definition['otherKey'] ?? null,
                    $definition['parentKey'] ?? null,
                    $definition['relatedKey'] ?? null,
                    $definition['inverse'] ?? false,
                );
                if (isset($definition['pivotModel'])) {
                    $relation->using($definition['pivotModel']);
                }
                break;
            case 'morphedByMany':
                $relation = $this->morphedByMany(
                    $relatedClass,
                    $definition['name'],
                    $definition['table'] ?? null,
                    $definition['key'] ?? null,
                    $definition['otherKey'] ?? null,
                    $definition['parentKey'] ?? null,
                    $definition['relatedKey'] ?? null,
                );
                break;
            case 'attachOne':
                $relation = $this->attachOne(
                    $relatedClass,
                    $definition['public'] ?? true,
                    $definition['key'] ?? null,
                    $definition['field'] ?? $relationName,
                );
                if (isset($definition['delete']) && $definition['delete'] === false) {
                    $relation = $relation->notDependent();
                }
                break;
            case 'attachMany':
                $relation = $this->attachMany(
                    $relatedClass,
                    $definition['public'] ?? true,
                    $definition['key'] ?? null,
                    $definition['field'] ?? $relationName,
                );
                if (isset($definition['delete']) && $definition['delete'] === false) {
                    $relation = $relation->notDependent();
                }
                break;
            case 'hasOneThrough':
                $relation = $this->hasOneThrough(
                    $relatedClass,
                    $definition['through'],
                    $definition['key'] ?? null,
                    $definition['throughKey'] ?? null,
                    $definition['otherKey'] ?? null,
                    $definition['secondOtherKey'] ?? null,
                );
                break;
            case 'hasManyThrough':
                $relation = $this->hasManyThrough(
                    $relatedClass,
                    $definition['through'],
                    $definition['key'] ?? null,
                    $definition['throughKey'] ?? null,
                    $definition['otherKey'] ?? null,
                    $definition['secondOtherKey'] ?? null,
                );
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf(
                        'There is no such relation type known as \'%s\' on model \'%s\'.',
                        $relationType,
                        get_called_class()
                    )
                );
        }

        // Add relation name
        $relation->setRelationName($relationName);

        // Add dependency, if required
        if (
            in_array(
                \Winter\Storm\Database\Relations\Concerns\CanBeDependent::class,
                class_uses_recursive($relation)
            )
            && (($definition['delete'] ?? false) === true)
        ) {
            $relation = $relation->dependent();
        }

        // Remove detachable, if required
        if (
            in_array(
                \Winter\Storm\Database\Relations\Concerns\CanBeDetachable::class,
                class_uses_recursive($relation)
            )
            && (($definition['detach'] ?? true) === false)
        ) {
            $relation = $relation->notDetachable();
        }

        // Remove pushable flag, if required
        if (
            in_array(
                \Winter\Storm\Database\Relations\Concerns\CanBePushed::class,
                class_uses_recursive($relation)
            )
            && (($definition['push'] ?? true) === false)
        ) {
            $relation = $relation->noPush();
        }

        if ($addConstraints) {
            // Add defined constraints
            $relation->addDefinedConstraints();
        }

        return $relation;
    }

    /**
     * Finds the calling function name from the stack trace.
     */
    protected function getRelationCaller(): ?string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $handled = Arr::first($trace, function ($trace) {
            return $trace['function'] === 'handleRelation';
        });

        if (!is_null($handled)) {
            return null;
        }

        $caller = Arr::first($trace, function ($trace) {
            return !in_array(
                $trace['class'],
                [
                    \Illuminate\Database\Eloquent\Model::class,
                    \Winter\Storm\Database\Model::class,
                ]
            );
        });

        return !is_null($caller) ? $caller['function'] : null;
    }

    /**
     * Returns a relation key value(s), not as an object.
     */
    public function getRelationValue($relationName)
    {
        return $this->$relationName()->getSimpleValue();
    }

    /**
     * Sets a relation value directly from its attribute.
     */
    protected function setRelationValue(string $relationName, $value): void
    {
        $this->$relationName()->setSimpleValue($value);
    }

    /**
     * Perform cascading deletions on related models that are dependent on the primary model.
     */
    protected function performDeleteOnRelations(): void
    {
        $relations = $this->getDefinedRelations();

        /** @var EloquentRelation */
        foreach (array_values($relations) as $relationObj) {
            if (method_exists($relationObj, 'isDetachable') && $relationObj->isDetachable()) {
                /** @var BelongsToMany|MorphToMany $relationObj */
                $relationObj->detach();
            }
            if (method_exists($relationObj, 'isDependent') && $relationObj->isDependent()) {
                $relationObj->get()->each(function ($model) {
                    $model->forceDelete();
                });
            }
        }
    }

    /**
     * Retrieves all methods that either contain the `Relation` attribute or have a return type that matches a relation.
     */
    public function getRelationMethods(): array
    {
        $relationMethods = [];

        foreach (get_class_methods($this) as $method) {
            if (!in_array($method, ['attachOne', 'attachMany']) && $this->isRelationMethod($method)) {
                $relationMethods[] = $method;
            }
        }

        return $relationMethods;
    }

    /**
     * Determines if the provided method name is a relation method.
     *
     * A relation method either specifies the `Relation` attribute or has a return type that matches a relation.
     */
    public function isRelationMethod(string $name): bool
    {
        if (!method_exists($this, $name)) {
            return false;
        }

        $method = new \ReflectionMethod($this, $name);

        if (count($method->getAttributes(Relation::class))) {
            return true;
        }

        $returnType = $method->getReturnType();

        if (is_null($returnType)) {
            return false;
        }

        if (
            $returnType instanceof \ReflectionNamedType
             && in_array($returnType->getName(), array_values(static::$relationTypes))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Generates a definition array for a relation method.
     */
    protected function relationMethodDefinition(string $name): array
    {
        if (!$this->isRelationMethod($name)) {
            return [];
        }

        return $this->{$name}()->getArrayDefinition();
    }

    /**
     * {@inheritDoc}
     */
    protected function newHasOne(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        $relation = new HasOne($query, $parent, $foreignKey, $localKey);
        $caller = $this->getRelationCaller();
        if (!is_null($caller)) {
            $relation->setRelationName($caller);
        }
        return $relation;
    }

    /**
     * {@inheritDoc}
     */
    protected function newHasOneThrough(Builder $query, Model $farParent, Model $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey)
    {
        $relation = new HasOneThrough($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
        $caller = $this->getRelationCaller();
        if (!is_null($caller)) {
            $relation->setRelationName($caller);
        }
        return $relation;
    }

    /**
     * {@inheritDoc}
     */
    protected function newMorphOne(Builder $query, Model $parent, $type, $id, $localKey)
    {
        $relation = new MorphOne($query, $parent, $type, $id, $localKey);
        $caller = $this->getRelationCaller();
        if (!is_null($caller)) {
            $relation->setRelationName($caller);
        }
        return $relation;
    }

    /**
     * {@inheritDoc}
     */
    public function guessBelongsToRelation()
    {
        return $this->getRelationCaller();
    }

    /**
     * {@inheritDoc}
     */
    public function guessBelongsToManyRelation()
    {
        return $this->getRelationCaller();
    }

    /**
     * {@inheritDoc}
     */
    protected function newBelongsTo(Builder $query, Model $child, $foreignKey, $ownerKey, $relation)
    {
        $relation = new BelongsTo($query, $child, $foreignKey, $ownerKey, $relation);
        $caller = $this->getRelationCaller();
        if (!is_null($caller)) {
            $relation->setRelationName($caller);
        }
        return $relation;
    }

    /**
     * {@inheritDoc}
     */
    protected function newMorphTo(Builder $query, Model $parent, $foreignKey, $ownerKey, $type, $relation)
    {
        $relation = new MorphTo($query, $parent, $foreignKey, $ownerKey, $type, $relation);
        $caller = $this->getRelationCaller();
        if (!is_null($caller)) {
            $relation->setRelationName($caller);
        }
        return $relation;
    }

    /**
     * {@inheritDoc}
     */
    protected function newHasMany(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        $relation = new HasMany($query, $parent, $foreignKey, $localKey);
        $caller = $this->getRelationCaller();
        if (!is_null($caller)) {
            $relation->setRelationName($caller);
        }
        return $relation;
    }

    /**
     * {@inheritDoc}
     */
    protected function newHasManyThrough(Builder $query, Model $farParent, Model $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey)
    {
        $relation = new HasManyThrough($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
        $caller = $this->getRelationCaller();
        if (!is_null($caller)) {
            $relation->setRelationName($caller);
        }
        return $relation;
    }

    /**
     * {@inheritDoc}
     */
    protected function newMorphMany(Builder $query, Model $parent, $type, $id, $localKey)
    {
        $relation = new MorphMany($query, $parent, $type, $id, $localKey);
        $caller = $this->getRelationCaller();
        if (!is_null($caller)) {
            $relation->setRelationName($caller);
        }
        return $relation;
    }

    /**
     * {@inheritDoc}
     */
    protected function newBelongsToMany(
        Builder $query,
        Model $parent,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $relationName = null
    ) {
        $relation = new BelongsToMany($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
        $caller = $this->getRelationCaller();
        if (!is_null($caller)) {
            $relation->setRelationName($caller);
        }
        return $relation;
    }

    /**
     * {@inheritDoc}
     */
    protected function newMorphToMany(
        Builder $query,
        Model $parent,
        $name,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $relationName = null,
        $inverse = false
    ) {
        $relation = new MorphToMany($query, $parent, $name, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName, $inverse);
        $caller = $this->getRelationCaller();
        if (!is_null($caller)) {
            $relation->setRelationName($caller);
        }
        return $relation;
    }

    /**
     * Define an attachment one-to-one (morphOne) relationship.
     */
    public function attachOne($related, $isPublic = true, $localKey = null, $fieldName = null): AttachOne
    {
        $instance = $this->newRelatedInstance($related);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        $fieldName = $fieldName ?? $this->getRelationCaller();

        $relation = new AttachOne($instance->newQuery(), $this, $table . '.attachment_type', $table . '.attachment_id', $isPublic, $localKey, $fieldName);

        // By default, attachments are dependent on primary models.
        $relation->dependent();

        $caller = $this->getRelationCaller();
        if (!is_null($caller)) {
            $relation->setRelationName($caller);
        }

        return $relation;
    }

    /**
     * Define an attachment one-to-many (morphMany) relationship.
     */
    public function attachMany($related, $isPublic = null, $localKey = null, $fieldName = null): AttachMany
    {
        $instance = $this->newRelatedInstance($related);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        $fieldName = $fieldName ?? $this->getRelationCaller();

        $relation = new AttachMany($instance->newQuery(), $this, $table . '.attachment_type', $table . '.attachment_id', $isPublic, $localKey, $fieldName);

        // By default, attachments are dependent on primary models.
        $relation->dependent();

        $caller = $this->getRelationCaller();
        if (!is_null($caller)) {
            $relation->setRelationName($caller);
        }

        return $relation;
    }

     /**
     * Dynamically add the provided relationship configuration to the local properties
     *
     * @throws InvalidArgumentException if the $type is invalid or if the $name is already in use
     */
    protected function addRelation(string $type, string $name, array $config): void
    {
        if (!in_array($type, array_keys(static::$relationTypes))) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot add the "%s" relation to %s, %s is not a valid relationship type.',
                    $name,
                    get_class($this),
                    $type
                )
            );
        }

        if ($this->hasRelation($name) || isset($this->{$name})) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot add the "%s" relation to %s, it conflicts with an existing relation, attribute, or property.',
                    $name,
                    get_class($this)
                )
            );
        }

        $this->{$type} = array_merge($this->{$type}, [$name => $config]);
    }

    /**
     * Dynamically add a HasOne relationship
     *
     * @throws InvalidArgumentException if the provided relationship is already defined
     */
    public function addHasOneRelation(string $name, array $config): void
    {
        $this->addRelation('hasOne', $name, $config);
    }

    /**
     * Dynamically add a HasMany relationship
     *
     * @throws InvalidArgumentException if the provided relationship is already defined
     */
    public function addHasManyRelation(string $name, array $config): void
    {
        $this->addRelation('hasMany', $name, $config);
    }

    /**
     * Dynamically add a BelongsTo relationship
     *
     * @throws InvalidArgumentException if the provided relationship is already defined
     */
    public function addBelongsToRelation(string $name, array $config): void
    {
        $this->addRelation('belongsTo', $name, $config);
    }

    /**
     * Dynamically add a BelongsToMany relationship
     *
     * @throws InvalidArgumentException if the provided relationship is already defined
     */
    public function addBelongsToManyRelation(string $name, array $config): void
    {
        $this->addRelation('belongsToMany', $name, $config);
    }

    /**
     * Dynamically add a MorphTo relationship
     *
     * @throws InvalidArgumentException if the provided relationship is already defined
     */
    public function addMorphToRelation(string $name, array $config): void
    {
        $this->addRelation('morphTo', $name, $config);
    }

    /**
     * Dynamically add a MorphOne relationship
     *
     * @throws InvalidArgumentException if the provided relationship is already defined
     */
    public function addMorphOneRelation(string $name, array $config): void
    {
        $this->addRelation('morphOne', $name, $config);
    }

    /**
     * Dynamically add a MorphMany relationship
     *
     * @throws InvalidArgumentException if the provided relationship is already defined
     */
    public function addMorphManyRelation(string $name, array $config): void
    {
        $this->addRelation('morphMany', $name, $config);
    }

    /**
     * Dynamically add a MorphToMany relationship
     *
     * @throws InvalidArgumentException if the provided relationship is already defined
     */
    public function addMorphToManyRelation(string $name, array $config): void
    {
        $this->addRelation('morphToMany', $name, $config);
    }

    /**
     * Dynamically add a MorphedByMany relationship
     *
     * @throws InvalidArgumentException if the provided relationship is already defined
     */
    public function addMorphedByManyRelation(string $name, array $config): void
    {
        $this->addRelation('morphedByMany', $name, $config);
    }

    /**
     * Dynamically add an AttachOne relationship
     *
     * @throws InvalidArgumentException if the provided relationship is already defined
     */
    public function addAttachOneRelation(string $name, array $config): void
    {
        $this->addRelation('attachOne', $name, $config);
    }

    /**
     * Dynamically add an AttachMany relationship
     *
     * @throws InvalidArgumentException if the provided relationship is already defined
     */
    public function addAttachManyRelation(string $name, array $config): void
    {
        $this->addRelation('attachMany', $name, $config);
    }

    /**
     * Dynamically add a(n) HasOneThrough relationship
     *
     * @throws InvalidArgumentException if the provided relationship is already defined
     */
    public function addHasOneThroughRelation(string $name, array $config): void
    {
        $this->addRelation('hasOneThrough', $name, $config);
    }

    /**
     * Dynamically add a(n) HasManyThrough relationship
     *
     * @throws InvalidArgumentException if the provided relationship is already defined
     */
    public function addHasManyThroughRelation(string $name, array $config): void
    {
        $this->addRelation('hasManyThrough', $name, $config);
    }
}
