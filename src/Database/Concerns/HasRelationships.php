<?php namespace Winter\Storm\Database\Concerns;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as CollectionBase;
use Illuminate\Database\Eloquent\Model;
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
     * @param string $name Relation name
     */
    public function hasRelation($name): bool
    {
        if (method_exists($this, $name) && $this->isRelationMethod($name)) {
            return true;
        }

        return $this->getRelationDefinition($name) !== null;
    }

    /**
     * Returns relationship details from a supplied name.
     * @param string $name Relation name
     */
    public function getRelationDefinition($name): ?array
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
     * @param string $type Relation type
     * @return array|string|null
     */
    public function getRelationTypeDefinitions($type)
    {
        if (in_array($type, array_keys(static::$relationTypes))) {
            return $this->{$type};
        }

        return [];
    }

    /**
     * Returns the given relation definition.
     * @param string $type Relation type
     * @param string $name Relation name
     * @return string|null
     */
    public function getRelationTypeDefinition($type, $name)
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
     * Determines whether the specified relation should be saved
     * when push() is called instead of save() on the model. Default: true.
     * @param  string  $name Relation name
     * @return boolean
     */
    public function isRelationPushable($name)
    {
        $definition = $this->getRelationDefinition($name);
        if (is_null($definition) || !array_key_exists('push', $definition)) {
            return true;
        }

        return (bool) $definition['push'];
    }

    /**
     * Returns default relation arguments for a given type.
     * @param string $type Relation type
     * @return array
     */
    protected function getRelationDefaults($type)
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
     * Looks for the relation and does the correct magic as Eloquent would require
     * inside relation methods. For more information, read the documentation of the mentioned property.
     * @param string $relationName the relation key, camel-case version
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    protected function handleRelation($relationName)
    {
        $relationType = $this->getRelationType($relationName);
        $relation = $this->getRelationDefinition($relationName);

        if (!isset($relation[0]) && $relationType != 'morphTo') {
            throw new InvalidArgumentException(sprintf(
                "Relation '%s' on model '%s' should have at least a classname.",
                $relationName,
                get_called_class()
            ));
        }

        if (isset($relation[0]) && $relationType == 'morphTo') {
            throw new InvalidArgumentException(sprintf(
                "Relation '%s' on model '%s' is a morphTo relation and should not contain additional arguments.",
                $relationName,
                get_called_class()
            ));
        }

        switch ($relationType) {
            case 'hasOne':
            case 'hasMany':
                $relation = $this->validateRelationArgs($relationName, ['key', 'otherKey']);
                /** @var HasOne|HasMany */
                $relationObj = $this->$relationType($relation[0], $relation['key'], $relation['otherKey'], $relationName);

                if ($relation['delete'] ?? false) {
                    $relationObj->dependent();
                }

                break;

            case 'belongsTo':
                $relation = $this->validateRelationArgs($relationName, ['key', 'otherKey']);
                $relationObj = $this->$relationType($relation[0], $relation['key'], $relation['otherKey'], $relationName);
                break;

            case 'belongsToMany':
                $relation = $this->validateRelationArgs($relationName, ['table', 'key', 'otherKey', 'parentKey', 'relatedKey', 'pivot', 'timestamps']);
                $relationObj = $this->$relationType($relation[0], $relation['table'], $relation['key'], $relation['otherKey'], $relation['parentKey'], $relation['relatedKey'], $relationName);

                if (isset($relation['pivotModel'])) {
                    $relationObj->using($relation['pivotModel']);
                }

                break;

            case 'morphTo':
                $relation = $this->validateRelationArgs($relationName, ['name', 'type', 'id']);
                $relationObj = $this->$relationType($relation['name'] ?: $relationName, $relation['type'], $relation['id']);
                break;

            case 'morphOne':
            case 'morphMany':
                $relation = $this->validateRelationArgs($relationName, ['type', 'id', 'key'], ['name']);
                /** @var MorphOne|MorphMany */
                $relationObj = $this->$relationType($relation[0], $relation['name'], $relation['type'], $relation['id'], $relation['key'], $relationName);

                if ($relation['delete'] ?? false) {
                    $relationObj->dependent();
                }

                break;

            case 'morphToMany':
                $relation = $this->validateRelationArgs($relationName, ['table', 'key', 'otherKey', 'parentKey', 'relatedKey', 'pivot', 'timestamps'], ['name']);
                $relationObj = $this->$relationType($relation[0], $relation['name'], $relation['table'], $relation['key'], $relation['otherKey'], $relation['parentKey'], $relation['relatedKey'], false, $relationName);

                if (isset($relation['pivotModel'])) {
                    $relationObj->using($relation['pivotModel']);
                }

                break;

            case 'morphedByMany':
                $relation = $this->validateRelationArgs($relationName, ['table', 'key', 'otherKey', 'parentKey', 'relatedKey', 'pivot', 'timestamps'], ['name']);
                $relationObj = $this->$relationType($relation[0], $relation['name'], $relation['table'], $relation['key'], $relation['otherKey'], $relation['parentKey'], $relation['relatedKey'], $relationName);
                break;

            case 'attachOne':
            case 'attachMany':
                $relation = $this->validateRelationArgs($relationName, ['public', 'key']);
                /** @var AttachOne|AttachMany */
                $relationObj = $this->$relationType($relation[0], $relation['public'], $relation['key'], $relationName);

                if ($relation['delete'] ?? false) {
                    $relationObj->dependent();
                }

                break;

            case 'hasOneThrough':
            case 'hasManyThrough':
                $relation = $this->validateRelationArgs($relationName, ['key', 'throughKey', 'otherKey', 'secondOtherKey'], ['through']);
                $relationObj = $this->$relationType($relation[0], $relation['through'], $relation['key'], $relation['throughKey'], $relation['otherKey'], $relation['secondOtherKey']);
                break;

            default:
                throw new InvalidArgumentException(sprintf("There is no such relation type known as '%s' on model '%s'.", $relationType, get_called_class()));
        }

        // Add relation name
        $relationObj->setRelationName($relationName);

        // Add defined constraints
        $relationObj->addDefinedConstraints();

        return $relationObj;
    }

    /**
     * Validate relation supplied arguments.
     */
    protected function validateRelationArgs($relationName, $optional, $required = [])
    {
        $relation = $this->getRelationDefinition($relationName);

        // Query filter arguments
        $filters = ['scope', 'conditions', 'order', 'pivot', 'timestamps', 'push', 'count', 'default'];

        foreach (array_merge($optional, $filters) as $key) {
            if (!array_key_exists($key, $relation)) {
                $relation[$key] = null;
            }
        }

        $missingRequired = [];
        foreach ($required as $key) {
            if (!array_key_exists($key, $relation)) {
                $missingRequired[] = $key;
            }
        }

        if ($missingRequired) {
            throw new InvalidArgumentException(sprintf(
                'Relation "%s" on model "%s" should contain the following key(s): %s',
                $relationName,
                get_called_class(),
                implode(', ', $missingRequired)
            ));
        }

        return $relation;
    }


    /**
     * Finds the calling function name from the stack trace.
     */
    protected function getRelationCaller()
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
    protected function setRelationValue($relationName, $value)
    {
        $this->$relationName()->setSimpleValue($value);
    }

    /**
     * Get the polymorphic relationship columns.
     *
     * @param  string  $name
     * @param  string|null  $type
     * @param  string|null  $id
     * @return array
     */
    protected function getMorphs($name, $type = null, $id = null)
    {
        return [$type ?: $name.'_type', $id ?: $name.'_id'];
    }

    /**
     * Locates relations with delete flag and cascades the delete event.
     * For pivot relations, detach the pivot record unless the detach flag is false.
     * @return void
     */
    protected function performDeleteOnRelations()
    {
        $definitions = $this->getRelationDefinitions();
        foreach ($definitions as $type => $relations) {
            /*
             * Hard 'delete' definition
             */
            foreach ($relations as $name => $options) {
                if (in_array($type, ['belongsToMany', 'morphToMany', 'morphedByMany'])) {
                    // we want to remove the pivot record, not the actual relation record
                    if (Arr::get($options, 'detach', true)) {
                        $this->{$name}()->detach();
                    }
                } elseif (in_array($type, ['belongsTo', 'hasOneThrough', 'hasManyThrough', 'morphTo'])) {
                    // the model does not own the related record, we should not remove it.
                    continue;
                } elseif (in_array($type, ['attachOne', 'attachMany', 'hasOne', 'hasMany', 'morphOne', 'morphMany'])) {
                    if (!Arr::get($options, 'delete', false)) {
                        continue;
                    }

                    // Attempt to load the related record(s)
                    if (!$relation = $this->{$name}) {
                        continue;
                    }

                    if ($relation instanceof Model) {
                        $relation->forceDelete();
                    } elseif ($relation instanceof CollectionBase) {
                        $relation->each(function ($model) {
                            $model->forceDelete();
                        });
                    }
                }
            }
        }

        // Find relation methods
        foreach ($this->getRelationMethods() as $relation) {
            $relationObj = $this->{$relation}();

            if (method_exists($relationObj, 'isDependent')) {
                if ($relationObj->isDependent()) {
                    $relationObj->forceDelete();
                }
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
            if ($this->isRelationMethod($method)) {
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
     * Define an attachment one-to-one relationship.
     * This code is a duplicate of Eloquent but uses a Storm relation class.
     * @return \Winter\Storm\Database\Relations\AttachOne
     */
    public function attachOne($related, $isPublic = true, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = $this->newRelatedInstance($related);

        list($type, $id) = $this->getMorphs('attachment', null, null);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        $relation = new AttachOne($instance->newQuery(), $this, $table . '.' . $type, $table . '.' . $id, $isPublic, $localKey);
        $relation->setRelationName($this->getRelationCaller());
        return $relation;
    }

    /**
     * Define an attachment one-to-many relationship.
     * This code is a duplicate of Eloquent but uses a Storm relation class.
     * @return \Winter\Storm\Database\Relations\AttachMany
     */
    public function attachMany($related, $isPublic = null, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = $this->newRelatedInstance($related);

        list($type, $id) = $this->getMorphs('attachment', null, null);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        $relation = new AttachMany($instance->newQuery(), $this, $table . '.' . $type, $table . '.' . $id, $isPublic, $localKey);
        $relation->setRelationName($this->getRelationCaller());
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
