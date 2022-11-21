<?php namespace Winter\Storm\Database\Concerns;

use InvalidArgumentException;
use Winter\Storm\Database\Model;
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
use Winter\Storm\Support\Str;

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
        'hasOne',
        'hasMany',
        'belongsTo',
        'belongsToMany',
        'morphTo',
        'morphOne',
        'morphMany',
        'morphToMany',
        'morphedByMany',
        'attachOne',
        'attachMany',
        'hasOneThrough',
        'hasManyThrough',
    ];

    //
    // Relations
    //

    /**
     * Checks if model has a relationship by supplied name.
     * @param string $name Relation name
     */
    public function hasRelation($name): bool
    {
        return $this->getRelationDefinition($name) !== null;
    }

    /**
     * Returns relationship details from a supplied name.
     * @param string $name Relation name
     */
    public function getRelationDefinition($name): ?array
    {
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
        if (in_array($type, static::$relationTypes)) {
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

        foreach (static::$relationTypes as $type) {
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
        foreach (static::$relationTypes as $type) {
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
                $relationObj = $this->$relationType($relation[0], $relation['key'], $relation['otherKey'], $relationName);
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
                $relationObj = $this->$relationType($relation[0], $relation['name'], $relation['type'], $relation['id'], $relation['key'], $relationName);
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
                $relationObj = $this->$relationType($relation[0], $relation['public'], $relation['key'], $relationName);
                break;

            case 'hasOneThrough':
            case 'hasManyThrough':
                $relation = $this->validateRelationArgs($relationName, ['key', 'throughKey', 'otherKey', 'secondOtherKey'], ['through']);
                $relationObj = $this->$relationType($relation[0], $relation['through'], $relation['key'], $relation['throughKey'], $relation['otherKey'], $relation['secondOtherKey']);
                break;

            default:
                throw new InvalidArgumentException(sprintf("There is no such relation type known as '%s' on model '%s'.", $relationType, get_called_class()));
        }

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
     * Define a one-to-one relationship.
     * This code is a duplicate of Eloquent but uses a Storm relation class.
     * @return \Winter\Storm\Database\Relations\HasOne
     */
    public function hasOne($related, $primaryKey = null, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = $this->newRelatedInstance($related);

        $primaryKey = $primaryKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return new HasOne($instance->newQuery(), $this, $instance->getTable().'.'.$primaryKey, $localKey, $relationName);
    }

    /**
     * Define a polymorphic one-to-one relationship.
     * This code is a duplicate of Eloquent but uses a Storm relation class.
     * @return \Winter\Storm\Database\Relations\MorphOne
     */
    public function morphOne($related, $name, $type = null, $id = null, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = $this->newRelatedInstance($related);

        list($type, $id) = $this->getMorphs($name, $type, $id);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        return new MorphOne($instance->newQuery(), $this, $table.'.'.$type, $table.'.'.$id, $localKey, $relationName);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Overridden from {@link Eloquent\Model} to allow the usage of the intermediary methods to handle the {@link
     * $relationsData} array.
     * @return \Winter\Storm\Database\Relations\BelongsTo
     */
    public function belongsTo($related, $foreignKey = null, $parentKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = $this->newRelatedInstance($related);

        if (is_null($foreignKey)) {
            $foreignKey = snake_case($relationName).'_id';
        }

        $parentKey = $parentKey ?: $instance->getKeyName();

        return new BelongsTo($instance->newQuery(), $this, $foreignKey, $parentKey, $relationName);
    }

    /**
     * Define an polymorphic, inverse one-to-one or many relationship.
     * Overridden from {@link Eloquent\Model} to allow the usage of the intermediary methods to handle the relation.
     * @return \Winter\Storm\Database\Relations\MorphTo
     */
    public function morphTo($name = null, $type = null, $id = null, $ownerKey = null)
    {
        if (is_null($name)) {
            $name = $this->getRelationCaller();
        }

        list($type, $id) = $this->getMorphs(Str::snake($name), $type, $id);

        return empty($class = $this->{$type})
                    ? $this->morphEagerTo($name, $type, $id, $ownerKey)
                    : $this->morphInstanceTo($class, $name, $type, $id, $ownerKey);
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     *
     * @param  string  $name
     * @param  string  $type
     * @param  string  $id
     * @param  string  $ownerKey
     * @return \Winter\Storm\Database\Relations\MorphTo
     */
    protected function morphEagerTo($name, $type, $id, $ownerKey)
    {
        return new MorphTo(
            $this->newQuery()->setEagerLoads([]),
            $this,
            $id,
            $ownerKey,
            $type,
            $name
        );
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     *
     * @param  string  $target
     * @param  string  $name
     * @param  string  $type
     * @param  string  $id
     * @param  string|null  $ownerKey
     * @return \Winter\Storm\Database\Relations\MorphTo
     */
    protected function morphInstanceTo($target, $name, $type, $id, $ownerKey = null)
    {
        $instance = $this->newRelatedInstance(
            static::getActualClassNameForMorph($target)
        );

        return new MorphTo(
            $instance->newQuery(),
            $this,
            $id,
            $ownerKey ?? $instance->getKeyName(),
            $type,
            $name
        );
    }

    /**
     * Define a one-to-many relationship.
     * This code is a duplicate of Eloquent but uses a Storm relation class.
     * @return \Winter\Storm\Database\Relations\HasMany
     */
    public function hasMany($related, $primaryKey = null, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = $this->newRelatedInstance($related);

        $primaryKey = $primaryKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return new HasMany($instance->newQuery(), $this, $instance->getTable().'.'.$primaryKey, $localKey, $relationName);
    }

    /**
     * Define a has-many-through relationship.
     * This code is a duplicate of Eloquent but uses a Storm relation class.
     * @return \Winter\Storm\Database\Relations\HasManyThrough
     */
    public function hasManyThrough($related, $through, $primaryKey = null, $throughKey = null, $localKey = null, $secondLocalKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $throughInstance = new $through;

        $primaryKey = $primaryKey ?: $this->getForeignKey();

        $throughKey = $throughKey ?: $throughInstance->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        $secondLocalKey = $secondLocalKey ?: $throughInstance->getKeyName();

        $instance = $this->newRelatedInstance($related);

        return new HasManyThrough($instance->newQuery(), $this, $throughInstance, $primaryKey, $throughKey, $localKey, $secondLocalKey, $relationName);
    }

    /**
     * Define a has-one-through relationship.
     * This code is a duplicate of Eloquent but uses a Storm relation class.
     * @return \Winter\Storm\Database\Relations\HasOneThrough
     */
    public function hasOneThrough($related, $through, $primaryKey = null, $throughKey = null, $localKey = null, $secondLocalKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $throughInstance = new $through;

        $primaryKey = $primaryKey ?: $this->getForeignKey();

        $throughKey = $throughKey ?: $throughInstance->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        $secondLocalKey = $secondLocalKey ?: $throughInstance->getKeyName();

        $instance = $this->newRelatedInstance($related);

        return new HasOneThrough($instance->newQuery(), $this, $throughInstance, $primaryKey, $throughKey, $localKey, $secondLocalKey, $relationName);
    }

    /**
     * Define a polymorphic one-to-many relationship.
     * This code is a duplicate of Eloquent but uses a Storm relation class.
     * @return \Winter\Storm\Database\Relations\MorphMany
     */
    public function morphMany($related, $name, $type = null, $id = null, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = $this->newRelatedInstance($related);

        list($type, $id) = $this->getMorphs($name, $type, $id);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        return new MorphMany($instance->newQuery(), $this, $table.'.'.$type, $table.'.'.$id, $localKey, $relationName);
    }

    /**
     * Define a many-to-many relationship.
     * This code is almost a duplicate of Eloquent but uses a Storm relation class.
     * @return \Winter\Storm\Database\Relations\BelongsToMany
     */
    public function belongsToMany($related, $table = null, $primaryKey = null, $foreignKey = null, $parentKey = null, $relatedKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = $this->newRelatedInstance($related);

        $primaryKey = $primaryKey ?: $this->getForeignKey();

        $foreignKey = $foreignKey ?: $instance->getForeignKey();

        if (is_null($table)) {
            $table = $this->joiningTable($related);
        }

        return new BelongsToMany(
            $instance->newQuery(),
            $this,
            $table,
            $primaryKey,
            $foreignKey,
            $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(),
            $relationName
        );
    }

    /**
     * Define a polymorphic many-to-many relationship.
     * This code is almost a duplicate of Eloquent but uses a Storm relation class.
     * @return \Winter\Storm\Database\Relations\MorphToMany
     */
    public function morphToMany($related, $name, $table = null, $primaryKey = null, $foreignKey = null, $parentKey = null, $relatedKey = null, $inverse = false, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = $this->newRelatedInstance($related);

        $primaryKey = $primaryKey ?: $name.'_id';

        $foreignKey = $foreignKey ?: $instance->getForeignKey();

        $table = $table ?: Str::plural($name);

        return new MorphToMany(
            $instance->newQuery(),
            $this,
            $name,
            $table,
            $primaryKey,
            $foreignKey,
            $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(),
            $relationName,
            $inverse
        );
    }

    /**
     * Define a polymorphic many-to-many inverse relationship.
     * This code is almost a duplicate of Eloquent but uses a Storm relation class.
     * @return \Winter\Storm\Database\Relations\MorphToMany
     */
    public function morphedByMany($related, $name, $table = null, $primaryKey = null, $foreignKey = null, $parentKey = null, $relatedKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $primaryKey = $primaryKey ?: $this->getForeignKey();

        $foreignKey = $foreignKey ?: $name.'_id';

        return $this->morphToMany(
            $related,
            $name,
            $table,
            $primaryKey,
            $foreignKey,
            $parentKey,
            $relatedKey,
            true,
            $relationName
        );
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

        return new AttachOne($instance->newQuery(), $this, $table . '.' . $type, $table . '.' . $id, $isPublic, $localKey, $relationName);
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

        return new AttachMany($instance->newQuery(), $this, $table . '.' . $type, $table . '.' . $id, $isPublic, $localKey, $relationName);
    }

    /**
     * Finds the calling function name from the stack trace.
     */
    protected function getRelationCaller()
    {
        $backtrace = debug_backtrace(0);
        $caller = ($backtrace[2]['function'] == 'handleRelation') ? $backtrace[4] : $backtrace[2];
        return $caller['function'];
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
     * Dynamically add the provided relationship configuration to the local properties
     *
     * @throws InvalidArgumentException if the $type is invalid or if the $name is already in use
     */
    protected function addRelation(string $type, string $name, array $config): void
    {
        if (!in_array($type, static::$relationTypes)) {
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
}
