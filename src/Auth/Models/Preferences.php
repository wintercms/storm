<?php namespace Winter\Storm\Auth\Models;

use Winter\Storm\Database\Model;
use Winter\Storm\Auth\AuthException;
use Winter\Storm\Auth\Manager;

/**
 * User Preferences model
 *
 * @property string|array|null $value Represents the value of the preference.
 * @property string|null $namespace Represents the namespace of the preference.
 * @property string|null $group Represents the group of the preference.
 * @property string|null $item Represents the item name of the preference.
 * @property int|null $user_id Represents the user ID that this preference belongs to.
 *
 * @method static \Winter\Storm\Database\QueryBuilder applyKeyAndUser($key, $user = null) Scope to find a setting record
 *  for the specified module (or plugin) name, setting name and user.
 */
class Preferences extends Model
{
    use \Winter\Storm\Support\Traits\KeyParser;

    /**
     * @var string The database table used by the model.
     */
    protected $table = 'preferences';

    public $timestamps = false;

    protected static $cache = [];

    /**
     * @var array List of attribute names which are json encoded and decoded from the database.
     */
    protected $jsonable = ['value'];

    /**
     * @var \Winter\Storm\Auth\Models\User|null A user who owns the preferences
     */
    public $userContext;

    /**
     * Checks for a supplied user or uses the default logged in. You should override this method.
     * @param mixed $user An optional back-end user object.
     * @return User object
     */
    public function resolveUser($user)
    {
        $user = Manager::instance()->getUser();

        if (!$user) {
            throw new AuthException('User is not logged in');
        }

        return $user;
    }

    /**
     * Creates this object and sets the user context
     */
    public static function forUser($user = null)
    {
        $self = new static;
        $self->userContext = $user ?: $self->resolveUser($user);
        return $self;
    }

    /**
     * Returns a setting value by the module (or plugin) name and setting name.
     * @param string $key Specifies the setting key value, for example 'backend:items.perpage'
     * @param mixed $default The default value to return if the setting doesn't exist in the DB.
     * @return mixed Returns the setting value loaded from the database or the default value.
     */
    public function get($key, $default = null)
    {
        $user = $this->userContext;

        if (!$user) {
            return $default;
        }

        $cacheKey = $this->getCacheKey($key, $user);

        if (array_key_exists($cacheKey, static::$cache)) {
            return static::$cache[$cacheKey];
        }

        $record = static::findRecord($key, $user);

        if (!$record) {
            return static::$cache[$cacheKey] = $default;
        }

        return static::$cache[$cacheKey] = $record->value;
    }

    /**
     * Stores a setting value to the database.
     * @param string $key Specifies the setting key value, for example 'backend:items.perpage'
     * @param mixed $value The setting value to store, serializable.
     * If the user is not provided the currently authenticated user will be used. If there is no
     * an authenticated user, the exception will be thrown.
     * @return bool
     */
    public function set($key, $value)
    {
        $user = $this->userContext;

        if (!$user) {
            return false;
        }

        $record = static::findRecord($key, $user);

        if (!$record) {
            list($namespace, $group, $item) = $this->parseKey($key);
            $record = new static;
            $record->namespace = $namespace;
            $record->group = $group;
            $record->item = $item;
            $record->user_id = $user->id;
        }

        $record->value = $value;
        $record->save();

        $cacheKey = $this->getCacheKey($key, $user);
        static::$cache[$cacheKey] = $value;
        return true;
    }

    /**
     * Resets a setting value by deleting the record.
     * @param string $key Specifies the setting key value.
     * @return bool
     */
    public function reset($key)
    {
        $user = $this->userContext;

        if (!$user) {
            return false;
        }

        $record = static::findRecord($key, $user);

        if (!$record) {
            return false;
        }

        $record->delete();

        $cacheKey = $this->getCacheKey($key, $user);
        unset(static::$cache[$cacheKey]);

        return true;
    }

    /**
     * Returns a record
     * @return self|null
     */
    public static function findRecord($key, $user = null)
    {
        return static::applyKeyAndUser($key, $user)->first();
    }

    /**
     * Scope to find a setting record for the specified module (or plugin) name, setting name and user.
     * @param \Winter\Storm\Database\QueryBuilder $query
     * @param string $key Specifies the setting key value, for example 'backend:items.perpage'
     * @param mixed $user An optional user object.
     * @return mixed Returns the found record or null.
     */
    public function scopeApplyKeyAndUser($query, $key, $user = null)
    {
        list($namespace, $group, $item) = $this->parseKey($key);

        $query = $query
            ->where('namespace', $namespace)
            ->where('group', $group)
            ->where('item', $item);

        if ($user) {
            $query = $query->where('user_id', $user->id);
        }

        return $query;
    }

    /**
     * Builds a cache key for the preferences record.
     * @return string
     */
    protected function getCacheKey($item, $user)
    {
        return $user->id . '-' . $item;
    }
}
