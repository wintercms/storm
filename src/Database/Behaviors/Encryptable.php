<?php namespace Winter\Storm\Database\Behaviors;

use App;
use Exception;

class Encryptable extends \Winter\Storm\Extension\ExtensionBase
{
    protected $model;

    /**
     * @var array List of attribute names which should be encrypted
     *
     * protected $encryptable = [];
     */

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter Encrypter instance.
     */
    protected $encrypterInstance;

    /**
     * @var array List of original attribute values before they were encrypted.
     */
    protected $originalEncryptableValues = [];

    public function __construct($parent)
    {
        $this->model = $parent;
        $this->bootEncryptable();
    }

    /**
     * Boot the encryptable trait for a model.
     * @return void
     */
    public function bootEncryptable()
    {
        $self = $this;

        if (!$this->model->methodExists('getEncryptableAttributes')) {
            throw new Exception(sprintf(
                'You must define a getEncryptableAttributes method in %s to use the Encryptable trait.',
                $this->model::class,
            ));
        }

        /*
         * Encrypt required fields when necessary
         */
        $this->model::extend(function ($model) use ($self) {
            $encryptable = $model->getEncryptableAttributes();
            $model->bindEvent('model.beforeSetAttribute', function ($key, $value) use ($model, $encryptable, $self) {
                if (in_array($key, $encryptable) && !is_null($value)) {
                    return $self->makeEncryptableValue($key, $value);
                }
            });
            $model->bindEvent('model.beforeGetAttribute', function ($key) use ($model, $encryptable, $self) {
                $attributes = $model->getAttributes();
                if (in_array($key, $encryptable) && array_get($attributes, $key) != null) {
                    return $self->getEncryptableValue($model, $key);
                }
            });
        });
    }

    /**
     * Encrypts an attribute value and saves it in the original locker.
     * @param  string $key   Attribute
     * @param  string $value Value to encrypt
     * @return string        Encrypted value
     */
    public function makeEncryptableValue($key, $value)
    {
        $this->originalEncryptableValues[$key] = $value;
        return $this->getEncrypter()->encrypt($value);
    }

    /**
     * Decrypts an attribute value
     * @param  string $model Model
     * @param  string $key Attribute
     * @return string      Decrypted value
     */
    public function getEncryptableValue($model, $key)
    {
        $attributes = $model->getAttributes();
        return isset($attributes[$key])
            ? $this->getEncrypter()->decrypt($attributes[$key])
            : null;
    }

    /**
     * Returns the original values of any encrypted attributes.
     * @return array
     */
    public function getOriginalEncryptableValues()
    {
        return $this->originalEncryptableValues;
    }

    /**
     * Returns the original values of any encrypted attributes.
     * @return mixed
     */
    public function getOriginalEncryptableValue($attribute)
    {
        return $this->originalEncryptableValues[$attribute] ?? null;
    }

    /**
     * Provides the encrypter instance.
     *
     * @return \Illuminate\Contracts\Encryption\Encrypter
     */
    public function getEncrypter()
    {
        return (!is_null($this->encrypterInstance)) ? $this->encrypterInstance : App::make('encrypter');
    }

    /**
     * Sets the encrypter instance.
     *
     * @param \Illuminate\Contracts\Encryption\Encrypter $encrypter
     * @return void
     */
    public function setEncrypter(\Illuminate\Contracts\Encryption\Encrypter $encrypter)
    {
        $this->encrypterInstance = $encrypter;
    }
}
