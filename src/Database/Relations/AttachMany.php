<?php

namespace Winter\Storm\Database\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany as MorphManyBase;
use Winter\Storm\Database\Attach\File as FileModel;

/**
 * @phpstan-property \Winter\Storm\Database\Model $parent
 */
class AttachMany extends MorphManyBase implements Relation
{
    use Concerns\AttachOneOrMany;
    use Concerns\CanBeCounted;
    use Concerns\CanBeDependent;
    use Concerns\CanBeExtended;
    use Concerns\CanBePushed;
    use Concerns\DefinedConstraints;
    use Concerns\HasRelationName;

    /**
     * Create a new attach many relationship instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $type
     * @param  string  $id
     * @param  bool  $isPublic
     * @param  string  $localKey
     * @param  string  $fieldName
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $type, $id, $isPublic, $localKey, $fieldName)
    {
        $this->fieldName = $fieldName;
        parent::__construct($query, $parent, $type, $id, $localKey);
        $this->public = $isPublic;
        $this->extendableRelationConstruct();
    }

    /**
     * {@inheritDoc}
     */
    public function setSimpleValue($value): void
    {
        /*
         * Newly uploaded file(s)
         */
        if ($this->isValidFileData($value)) {
            $this->parent->bindEventOnce('model.afterSave', function () use ($value) {
                $this->create(['data' => $value]);
            });
        }
        elseif (is_array($value)) {
            $files = [];
            foreach ($value as $_value) {
                if ($this->isValidFileData($_value)) {
                    $files[] = $_value;
                }
            }
            $this->parent->bindEventOnce('model.afterSave', function () use ($files) {
                foreach ($files as $file) {
                    $this->create(['data' => $file]);
                }
            });
        }
        /*
         * Existing File model
         */
        elseif ($value instanceof FileModel) {
            $this->parent->bindEventOnce('model.afterSave', function () use ($value) {
                $this->add($value);
            });
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getSimpleValue(): mixed
    {
        $value = null;

        $files = $this->getSimpleValueInternal();

        if ($files) {
            $value = [];
            foreach ($files as $file) {
                $value[] = $file->getPath();
            }
        }

        return $value;
    }

    /**
     * Helper for getting this relationship validation value.
     */
    public function getValidationValue()
    {
        if ($value = $this->getSimpleValueInternal()) {
            $files = [];
            foreach ($value as $file) {
                $files[] = $this->makeValidationFile($file);
            }

            return $files;
        }

        return null;
    }

    /**
     * Internal method used by `getSimpleValue` and `getValidationValue`
     */
    protected function getSimpleValueInternal()
    {
        $value = null;

        $files = ($sessionKey = $this->parent->sessionKey)
            ? $this->withDeferred($sessionKey)->get()
            : $this->parent->{$this->relationName};

        if ($files) {
            $value = [];
            $files->each(function ($file) use (&$value) {
                $value[] = $file;
            });
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getArrayDefinition(): array
    {
        return [
            get_class($this->query->getModel()),
            'key' => $this->localKey,
            'delete' => $this->isDependent(),
            'public' => $this->public,
            'push' => $this->isPushable(),
            'count' => $this->isCountOnly(),
        ];
    }
}
