<?php namespace Winter\Storm\Database\Traits;

use Illuminate\Support\Facades\DB;
use Exception;
use DateTime;
use Illuminate\Database\Eloquent\Model as EloquentModel;

trait Revisionable
{

    /**
     * @var array List of attributes to monitor for changes and store revisions for.
     *
     * protected $revisionable = [];
     */

    /**
     * @var int Maximum number of revision records to keep.
     *
     * public $revisionableLimit = 500;
     */

    /**
     * You can change the relation name used to store revisions:
     *
     * const REVISION_HISTORY = 'revision_history';
     */

    /**
     * @var bool Purge revision history on deletion.
     *
     * protected $purgeRevisions = false;
     */

    /**
     * @var bool Flag for arbitrarily disabling revision history.
     */
    public $revisionsEnabled = true;

    /**
     * Boot the revisionable trait for a model.
     * @return void
     */
    public static function bootRevisionable()
    {
        if (!property_exists(get_called_class(), 'revisionable')) {
            throw new Exception(sprintf(
                'You must define a $revisionable property in %s to use the Revisionable trait.',
                get_called_class()
            ));
        }

        static::extend(function ($model) {
            $model->bindEvent('model.afterUpdate', function () use ($model) {
                $model->revisionableAfterUpdate();
            });

            $model->bindEvent('model.afterDelete', function () use ($model) {
                $model->revisionableAfterDelete();
            });
        });
    }

    public function revisionableAfterUpdate()
    {
        if (!$this->revisionsEnabled) {
            return;
        }

        $toSave = [];
        $dirty = $this->getDirty();
        foreach ($dirty as $attribute => $value) {
            if (!in_array($attribute, $this->revisionable)) {
                continue;
            }

            $toSave[] = [
                'field' => $attribute,
                'old_value' => array_get($this->original, $attribute),
                'new_value' => $value,
                'revisionable_type' => $this->getRevisionMorphClass(),
                'revisionable_id' => $this->getKey(),
                'user_id' => $this->revisionableGetUser(),
                'cast' => $this->revisionableGetCastType($attribute),
                'created_at' => new DateTime,
                'updated_at' => new DateTime
            ];
        }

        // Nothing to do
        if (!count($toSave)) {
            return;
        }

        $this->queryRevisionTable()->insert($toSave);
        $this->revisionableCleanUp();
    }

    public function revisionableAfterDelete()
    {
        if (!$this->revisionsEnabled) {
            return;
        }

        $softDeletes = in_array(
            'Winter\Storm\Database\Traits\SoftDelete',
            class_uses_recursive(get_class($this))
        );

        // If this model is not soft-deleted, delete revision history if "purgeRevisions" is enabled.
        if (!$softDeletes) {
            if (property_exists($this, 'purgeRevisions') && $this->purgeRevisions === true) {

            }
            return;
        }

        if (!in_array('deleted_at', $this->revisionable)) {
            return;
        }

        $toSave = [
            'field' => 'deleted_at',
            'old_value' => null,
            'new_value' => $this->deleted_at,
            'revisionable_type' => $this->getRevisionMorphClass(),
            'revisionable_id' => $this->getKey(),
            'user_id' => $this->revisionableGetUser(),
            'created_at' => new DateTime,
            'updated_at' => new DateTime
        ];

        $this->queryRevisionTable()->insert($toSave);
        $this->revisionableCleanUp();
    }

    /**
     * Gets the relation model.
     *
     * @return \Winter\Storm\Database\Relations\MorphToMany;
     */
    protected function getRevisionRelationObject()
    {
        $relation = $this->getRevisionHistoryName();
        return $this->{$relation}();
    }

    /**
     * Creates a new query to the revision table.
     *
     * @return \Winter\Storm\Database\Builder
     */
    protected function queryRevisionTable()
    {
        return DB::table($this->getRevisionRelationObject()->getRelated()->getTable());
    }

    /**
     * Gets the class name to morph to for the revision.
     *
     * @return string
     */
    protected function getRevisionMorphClass()
    {
        return $this->getRevisionRelationObject()->getMorphClass();
    }

    /*
     * Deletes revision records exceeding the limit.
     */
    protected function revisionableCleanUp()
    {
        $revisionLimit = property_exists($this, 'revisionableLimit')
            ? (int) $this->revisionableLimit
            : 500;

        $toDelete = $this->getRevisionRelationObject()
            ->orderBy('id', 'desc')
            ->skip($revisionLimit)
            ->limit(64)
            ->get();

        foreach ($toDelete as $record) {
            $record->delete();
        }
    }

    protected function revisionableGetCastType($attribute)
    {
        if (in_array($attribute, $this->getDates())) {
            return 'date';
        }

        return null;
    }

    protected function revisionableGetUser()
    {
        if (method_exists($this, 'getRevisionableUser')) {
            $user = $this->getRevisionableUser();

            return $user instanceof EloquentModel
                ? $user->getKey()
                : $user;
        }

        return null;
    }

    /**
     * Get revision history relation name name.
     * @return string
     */
    public function getRevisionHistoryName()
    {
        return defined('static::REVISION_HISTORY') ? static::REVISION_HISTORY : 'revision_history';
    }
}
