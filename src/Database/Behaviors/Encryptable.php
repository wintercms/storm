<?php namespace Winter\Storm\Database\Behaviors;

use App;
use Exception;
use Illuminate\Contracts\Encryption\Encrypter;
use Winter\Storm\Extension\ExtensionBase;

/**
 * Encryptable model behavior
 *
 * Usage:
 *
 * In the model class definition:
 *
 *     public $implement = [
 *         \Winter\Storm\Database\Behaviors\Encryptable::class,
 *     ];
 *
 *     /**
 *      * List of attributes to encrypt.
 *      * /
 *     protected array $encryptable = ['api_key', 'api_secret'];
 *
 * Dynamically attached to third party model:
 *
 *     TargetModel::extend(function ($model) {
 *         $model->addDynamicProperty('encryptable', ['encrypt_this']);
 *         $model->extendClassWith(\Winter\Storm\Database\Behaviors\Encryptable::class);
 *     });
 *
 * >**NOTE**: Encrypted attributes will be serialized and unserialized
 * as a part of the encryption / decryption process. Do not make an 
 * attribute that is encryptable also jsonable at the same time as the
 * jsonable process will attempt to decode a value that has already been
 * unserialized by the encrypter.
 *
 */
class Encryptable extends ExtensionBase
{
    protected $model;

    /**
     * List of attribute names which should be encrypted
     *
     * protected array $encryptable = [];
     */

    /**
     * Encrypter instance.
     */
    protected Encrypter $encrypterInstance;

    /**
     * List of original attribute values before they were encrypted.
     */
    protected array $originalEncryptableValues = [];

    public function __construct($parent)
    {
        $this->model = $parent;
        $this->bootEncryptable();
    }

    /**
     * Boot the encryptable trait for a model.
     */
    public function bootEncryptable(): void
    {
        if (!property_exists(get_class(), 'encryptable')) {
            throw new Exception(sprintf(
                'You must define a $encryptable property in %s to use the Encryptable trait.',
                get_called_class()
            ));
        }

        if (!$this->model->methodExists('getEncryptableAttributes')) {
            throw new Exception(sprintf(
                'You must define a getEncryptableAttributes method in %s to use the Encryptable trait.',
                $this->model::class,
            ));
        }

        /*
         * Encrypt required fields when necessary
         */
        $this->model::extend(function ($model) {
            $encryptable = $model->getEncryptableAttributes();
            $model->bindEvent('model.beforeSetAttribute', function ($key, $value) use ($model, $encryptable) {
                if (in_array($key, $encryptable) && !is_null($value)) {
                    return $model->makeEncryptableValue($key, $value);
                }
            });
            $model->bindEvent('model.beforeGetAttribute', function ($key) use ($model, $encryptable) {
                if (in_array($key, $encryptable) && array_get($model->getAttributes(), $key) != null) {
                    return $model->getEncryptableValue($key);
                }
            });
        });
    }

    /**
     * Encrypts an attribute value and saves it in the original locker.
     */
    public function makeEncryptableValue(string $key, mixed $value): string
    {
        $this->originalEncryptableValues[$key] = $value;
        return $this->model->getEncrypter()->encrypt($value);
    }

    /**
     * Decrypts an attribute value
     */
    public function getEncryptableValue(string $key): ?mixed
    {
        $attributes = $this->model->getAttributes();
        return isset($attributes[$key])
            ? $this->model->getEncrypter()->decrypt($attributes[$key])
            : null;
    }

    /**
     * Returns the original values of any encrypted attributes.
     */
    public function getOriginalEncryptableValues(): array
    {
        return $this->originalEncryptableValues;
    }

    /**
     * Returns the original values of any encrypted attributes.
     */
    public function getOriginalEncryptableValue(string $attribute): ?mixed
    {
        return $this->originalEncryptableValues[$attribute] ?? null;
    }

    /**
     * Provides the encrypter instance.
     */
    public function getEncrypter(): Encrypter
    {
        return (!is_null($this->encrypterInstance)) ? $this->encrypterInstance : App::make('encrypter');
    }

    /**
     * Sets the encrypter instance.
     */
    public function setEncrypter(Encrypter $encrypter): void
    {
        $this->encrypterInstance = $encrypter;
    }
}
