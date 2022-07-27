<?php namespace Winter\Storm\Database\Traits;

use Exception;

/**
 * Enables nullification of empty values on model attributes.
 *
 * A model that uses this class must provide a property `$nullable`, that defines as an array all columns that will be
 * set to `null` if they contain an empty value.
 */
trait Nullable
{
    /**
     * Boot the nullable trait for a model
     *
     * @return void
     */
    public static function bootNullable()
    {
        if (!property_exists(get_called_class(), 'nullable')) {
            throw new Exception(sprintf(
                'You must define a $nullable property in %s to use the Nullable trait.',
                get_called_class()
            ));
        }

        static::extend(function ($model) {
            $model->bindEvent('model.beforeSave', function () use ($model) {
                $model->nullableBeforeSave();
            });
        });
    }

    /**
     * Adds an attribute to the nullable attributes list
     * @param  array|string|null  $attributes
     * @return $this
     */
    public function addNullable($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->nullable = array_merge($this->nullable, $attributes);

        return $this;
    }

    /**
     * Checks if the supplied value is empty, excluding zero.
     * @param  mixed $value Value to check
     * @return bool
     */
    public function checkNullableValue($value)
    {
        if ($value === 0 || $value === '0' || $value === 0.0 || $value === false) {
            return false;
        }

        return empty($value);
    }

    /**
     * Nullify empty fields
     * @return void
     */
    public function nullableBeforeSave()
    {
        foreach ($this->nullable as $field) {
            if ($this->checkNullableValue($this->{$field})) {
                if ($this->exists) {
                    $this->attributes[$field] = null;
                }
                else {
                    unset($this->attributes[$field]);
                }
            }
        }
    }
}
