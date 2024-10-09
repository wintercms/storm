<?php

namespace Winter\Storm\Database\Relations\Concerns;

/**
 * This trait is used to mark certain relationships as soft deletable, where the record is made invisible from the
 * system but not actually removed from the database.
 *
 * This allows the relationship to be automatically soft deleted when the primary model is deleted.
 *
 * This trait provides a declarative way to mark a relationship as soft-deletable, by simply adding `->softDeletable()`
 * to the relationship definition method. For example:
 *
 * ```php
 * public function messages()
 * {
 *     return $this->hasMany(Message::class)->softDeletable();
 * }
 * ```
 *
 * If you are using the array-style definition, you can use the `softDelete` key to mark the relationship as
 * soft-deletable.
 *
 * ```php
 * public $hasMany = [
 *     'messages' => [Message::class, 'softDelete' => true]
 * ];
 * ```
 *
 * Please note that the related model must import the `Winter\Storm\Database\Traits\SoftDelete` trait in order to be
 * marked as soft-deletable.
 *
 * @author Ben Thomson <git@alfreido.com>
 * @copyright Winter CMS Maintainers
 */
trait CanBeSoftDeleted
{
    /**
     * Is this relation dependent on the primary model?
     */
    protected bool $isSoftDeletable = false;

    /**
     * Defines the column that stores the "deleted at" timestamp.
     */
    protected string $deletedAtColumn = 'deleted_at';

    /**
     * Mark the relationship as soft deletable.
     */
    public function softDeletable(bool $enabled = true): static
    {
        if (in_array('Winter\Storm\Database\Traits\SoftDelete', class_uses_recursive($this->related))) {
            $this->isSoftDeletable = $enabled;
        }

        return $this;
    }

    /**
     * Mark the relationship as not soft deletable (will be hard-deleted instead if the `dependent` option is set).
     */
    public function notSoftDeletable(): static
    {
        $this->isSoftDeletable = false;

        return $this;
    }

    /**
     * Determine if the related model is soft-deleted when the primary model is deleted.
     */
    public function isSoftDeletable(): bool
    {
        return $this->isSoftDeletable;
    }

    /**
     * Gets the column that stores the "deleted at" timestamp.
     */
    public function getDeletedAtColumn(): string
    {
        return $this->deletedAtColumn;
    }
}
