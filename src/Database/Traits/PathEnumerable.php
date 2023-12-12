<?php

namespace Winter\Storm\Database\Traits;

use Illuminate\Database\Eloquent\SoftDeletingScope;
use Winter\Storm\Database\Builder;
use Winter\Storm\Database\Collection;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\TreeCollection;
use Winter\Storm\Exception\ApplicationException;

/**
 * "Enumerable path" model trait
 *
 * Provides an implementation of "path enumeration" in PHP, storing hierarchal data using a single "path" column that
 * contains the an ID path to a specific record.
 *
 * It can be added to a model with the following:
 *
 * ```php
 * use Winter\Storm\Database\Traits\PathEnumerable;
 * ```
 *
 * By default, an "enumerable path" model must have a `parent_id` and a `path` column in the database table, but these
 * columns can be changed by defining the following constants in the model:
 *
 * ```php
 * const PARENT_ID = 'my_parent_id';
 * const PATH_COLUMN = 'my_path_column';
 * ```
 *
 * Include the following columns in your database table migration - ensuring that the column names match the constants
 * or the default column names:
 *
 * ```php
 * $table->integer('parent_id')->unsigned()->nullable();
 * $table->string('path')->nullable();
 * ```
 *
 * @author Ben Thomson <git@alfreido.com>
 * @copyright Winter CMS
 * @link https://www.waitingforcode.com/mysql/managing-hierarchical-data-in-mysql-path-enumeration/read
 * @link https://vadimtropashko.wordpress.com/2008/08/09/one-more-nested-intervals-vs-adjacency-list-comparison/
 */
trait PathEnumerable
{
    /**
     * Stores the new parent ID on update. If set to `false`, no change is pending.
     */
    protected int|null|false $newParentId = false;

    /**
     * Defines the column name that will be used for the path segments. By default, the ID of the record will be used.
     *
     * protected string $segmentColumn = 'id';
     */

    /**
     * Defines the column used for storing the parent ID. By default, this will be `parent_id`.
     *
     * const PARENT_ID = 'parent_id';
     */

    /**
     * Defines the column used for storing the path. By default, this will be `path`.
     *
     * const PATH_COLUMN = 'path';
     */

    public static function bootPathEnumerable(): void
    {
        static::extend(function (Model $model) {
            // Define relationships

            $model->hasMany['children'] = [
                get_class($model),
                'key' => $model->getParentColumnName()
            ];

            $model->belongsTo['parent'] = [
                get_class($model),
                'key' => $model->getParentColumnName()
            ];

            // Add event listeners
            $model->bindEvent('model.afterCreate', function () use ($model) {
                $model->setEnumerablePath();
            });

            $model->bindEvent('model.beforeUpdate', function () use ($model) {
                $model->storeNewParent();
            });

            $model->bindEvent('model.afterUpdate', function () use ($model) {
                $model->moveToNewParent();
            });

            $model->bindEvent('model.beforeDelete', function () use ($model) {
                $model->deleteDescendants();
            });

            if (static::hasGlobalScope(SoftDeletingScope::class)) {
                $model->bindEvent('model.afterRestore', function () use ($model) {
                    $model->restoreDescendants();
                    $model->setEnumerablePath();
                });
            }
        });
    }

    /**
     * Gets the direct parent of the current record.
     */
    public function getParent(): Collection
    {
        return $this->parent()->get();
    }

    /**
     * Gets all ancestral records of the current record.
     */
    public function getParents(): Collection
    {
        return $this->newQuery()->ancestors()->get();
    }

    /**
     * Gets all direct children of the current record.
     */
    public function getChildren(): Collection
    {
        return $this->children()->get();
    }

    /**
     * Gets all children (ancestors) of the current record.
     *
     * This will include children records of the child records, and so on.
     */
    public function getAllChildren(): Collection
    {
        return $this->newQuery()->descendants()->get();
    }

    /**
     * Gets a nested collection of all records.
     */
    public function getNested(): Collection
    {
        return $this->newQuery()->get()->toNested();
    }

    /**
     * Root nodes scope.
     *
     * Gets all record that form the root nodes of the hierarchy.
     */
    public function scopeRoot(Builder $query): void
    {
        $query->whereNull($this->getParentColumnName());
    }

    /**
     * Descendants scope.
     *
     * Gets all children records, and all children of those records, and so on.
     */
    public function scopeDescendants(Builder $query): void
    {
        if (!$this->exists()) {
            // Nullify the query, as this record does not yet exist within the hierarchy
            $query->whereRaw('0 = 1');
        }

        $query->where($this->getPathColumnName(), 'LIKE', $this->getPath() . '/%');
    }

    /**
     * Ancestors scope.
     *
     * Gets all records that are direct ancestors (parents) of the current record.
     */
    public function scopeAncestors(Builder $query): void
    {
        if (!$this->exists()) {
            // Nullify the query, as this record does not yet exist within the hierarchy
            $query->whereRaw('0 = 1');
        }

        $ancestorPaths = $this->getAncestorPaths();

        if (!count($ancestorPaths)) {
            // Nullify the query, as this record has no ancestors.
            $query->whereRaw('0 = 1');
        }

        $query->whereIn($this->getPathColumnName(), $this->getAncestorPaths());
    }

    /**
     * Gets the enumerable path on the current record.
     *
     * This will take into account any parent changes, allowing you to get the new path before the record is saved.
     */
    public function getEnumerablePath(): string
    {
        if ($this->parent()->exists()) {
            return $this->parent->{$this->getPathColumnName()} . '/' . $this->getEnumerableSegment();
        }

        return '/' . $this->getEnumerableSegment();
    }

    public function getSegmentColumn(): string
    {
        if (!property_exists($this, 'segmentColumn')) {
            return $this->primaryKey;
        }

        return $this->segmentColumn;
    }

    /**
     * Gets the enumerable segment of this record.
     *
     * By default, this will return the ID of the record to make up each segment of the path. You can change the column
     * that makes up the path segments by defining another column name in the `$segmentColumn` property.
     *
     * @return string
     */
    public function getEnumerableSegment(): string
    {
        if (!array_key_exists($this->getSegmentColumn(), $this->attributes)) {
            throw new ApplicationException(
                sprintf(
                    'The segment column "%s" does not exist on the model "%s".',
                    $this->segmentColumn,
                    get_class($this)
                )
            );
        }

        return preg_replace('/(?<!\\\\)\//', '\\/', (string) $this->getAttribute($this->getSegmentColumn()));
    }

    /**
     * Sets the enumerable path on the current record.
     */
    public function setEnumerablePath(): void
    {
        $this->{$this->getPathColumnName()} = $path = $this->getEnumerablePath();

        $this->newQuery()
            ->where($this->getKeyName(), $this->id)
            ->update([$this->getPathColumnName() => $path]);
    }

    /**
     * Stores the new parent ID in preparation for an update.
     */
    public function storeNewParent(): void
    {
        $isDirty = $this->isDirty($this->getParentColumnName());

        if (!$isDirty) {
            return;
        }

        $this->newParentId = $this->getParentId();
    }

    /**
     * Moves a record, and all of its children, to a new parent.
     *
     * This will update the enumerated paths of all records.
     */
    public function moveToNewParent(): void
    {
        if ($this->newParentId === false) {
            return;
        }

        $oldPath = $this->getPath();
        $newPath = $this->getEnumerablePath();

        $this->getConnection()->transaction(function () use ($oldPath, $newPath) {
            foreach ($this->getAllChildren() as $child) {
                $child->{$this->getPathColumnName()} = str_replace(
                    $oldPath . '/',
                    $newPath . '/',
                    $child->{$this->getPathColumnName()}
                );
                $child->saveQuietly();
            }
        });

        $this->setEnumerablePath();
        $this->newParentId = false;
    }

    /**
     * Deletes all descendants.
     */
    public function deleteDescendants(): void
    {
        $this->newQuery()->descendants()->delete();
    }

    /**
     * Deletes all descendants.
     */
    public function restoreDescendants(): void
    {
        $this->newQuery()->descendants()->restore();
    }

    /**
     * Determines the depth of the current record.
     *
     * A root node is considered a depth of `0`. A child node of a root node is considered a depth of `1`, and so on.
     */
    public function getDepth(): int
    {
        return count(preg_split('/(?<!\\\\)\//', $this->getPath())) - 2;
    }

    /**
     * Gets the parent column name.
     */
    public function getParentColumnName(): string
    {
        return defined('static::PARENT_ID') ? constant('static::PARENT_ID') : 'parent_id';
    }

    /**
     * Gets the path column name.
     */
    public function getPathColumnName(): string
    {
        return defined('static::PATH_COLUMN') ? constant('static::PATH_COLUMN') : 'path';
    }

    /**
     * Gets the ID of the parent record for the current record.
     *
     * This will be `null` if the record has no parent (root node).
     */
    public function getParentId(): ?int
    {
        return $this->getAttribute($this->getParentColumnName());
    }

    /**
     * Gets the paths of all direct ancestors of the current record.
     *
     * @return int[]
     */
    public function getAncestorPaths(): array
    {
        $ids = preg_split('/(?<!\\\\)\//', $this->getPath());
        array_shift($ids);
        array_pop($ids);

        if (!count($ids)) {
            return [];
        }

        $paths = [];

        for ($i = 1; $i <= count($ids); $i++) {
            $paths[] = '/' . implode('/', array_slice($ids, 0, $i));
        }

        return $paths;
    }

    /**
     * Gets the current path of the record.
     */
    public function getPath(): string
    {
        return $this->getAttribute($this->getPathColumnName());
    }

    /**
     * Return a custom TreeCollection collection
     *
     * @param Model[] $models
     */
    public function newCollection(array $models = []): TreeCollection
    {
        return new TreeCollection($models);
    }
}
