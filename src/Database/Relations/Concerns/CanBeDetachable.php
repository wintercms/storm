<?php

namespace Winter\Storm\Database\Relations\Concerns;

/**
 * This trait is used to mark certain relationships as detachable.
 *
 * Similar to the `CanBeDependent` trait, this trait provides a declarative way to mark a relationship as detachable,
 * where a relation is detached when the primary model is deleted. The following relations support being marked as
 * detachable:
 *
 * - `belongsToMany`
 * - `morphToMany`
 * - `morphedByMany`
 *
 * This trait provides a declarative way to mark a relationship as detachable, by simply adding `->detachable()` to the
 * relationship definition method. For example:
 *
 * ```php
 * public function users()
 * {
 *     return $this->belongsToMany(User::class)->detachable();
 * }
 * ```
 *
 * If you are using the array-style definition, you can use the `detach` key to mark the relationship as detachable.
 *
 * ```php
 * public $belongsToMany = [
 *     'users' => [User::class, 'detach' => true]
 * ];
 * ```
 *
 * @author Ben Thomson <git@alfreido.com>
 * @copyright Winter CMS Maintainers
 */
trait CanBeDetachable
{
    /**
     * Should this relation be detached when the primary model is deleted?
     */
    protected bool $detachable = true;

    /**
     * Allow this relationship to be detached when the primary model is deleted.
     */
    public function detachable(): static
    {
        $this->detachable = true;

        return $this;
    }

    /**
     * Disallow this relationship to be detached when the primary model is deleted.
     */
    public function notDetachable(): static
    {
        $this->detachable = false;

        return $this;
    }

    /**
     * Determine if the relation should be detached when the primary model is deleted.
     */
    public function isDetachable(): bool
    {
        return $this->detachable;
    }
}
