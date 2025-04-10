<?php namespace Winter\Storm\Database\Behaviors;

use App;
use Illuminate\Contracts\Encryption\Encrypter;
use Winter\Storm\Database\Model;
use Winter\Storm\Exception\ApplicationException;
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
    protected Model $model;

    /**
     * List of attribute names which should be encrypted
     *
     * protected array $encryptable = [];
     */

    /**
     * Encrypter instance.
     */
    protected ?Encrypter $encrypterInstance = null;

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
        $isEncryptable = $this->model->extend(function () {
            /** @var Model $this */
            return $this->propertyExists('encryptable');
        });

        if (!$isEncryptable) {
            throw new ApplicationException(sprintf(
                'You must define an $encryptable property on the %s class to use the Encryptable behavior.',
                get_class($this->model)
            ));
        }

        /*
         * Encrypt required fields when necessary
         */
        $this->model->bindEvent('model.beforeSetAttribute', function ($key, $value) {
            if (in_array($key, $this->getEncryptableAttributes()) && !is_null($value)) {
                return $this->makeEncryptableValue($key, $value);
            }
        });
        $this->model->bindEvent('model.beforeGetAttribute', function ($key) {
            if (in_array($key, $this->getEncryptableAttributes()) && array_get($this->model->attributes, $key) != null) {
                return $this->getEncryptableValue($key);
            }
        });
    }

    /**
     * Encrypts an attribute value and saves it in the original locker.
     */
    public function makeEncryptableValue(string $key, mixed $value): string
    {
        $this->originalEncryptableValues[$key] = $value;
        return $this->getEncrypter()->encrypt($value);
    }

    /**
     * Decrypts an attribute value
     */
    public function getEncryptableValue(string $key): mixed
    {
        $attributes = $this->model->getAttributes();
        return isset($attributes[$key])
            ? $this->getEncrypter()->decrypt($attributes[$key])
            : null;
    }

    /**
     * Returns a collection of fields that will be encrypted.
     */
    public function getEncryptableAttributes(): array
    {
        return $this->model->extend(function () {
            return $this->encryptable ?? [];
        });
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
    public function getOriginalEncryptableValue(string $attribute): mixed
    {
        return array_get($this->originalEncryptableValues, $attribute, null);
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
