<?php

namespace Winter\Storm\Database\Relations\Concerns;

/**
 * This trait is used to mark certain relationships as dependent.
 *
 * A dependent relationship means that the related models for this relationship will be deleted when the primary
 * model is deleted. It can be used for the following relationship types:
 *
 * - `attachOne`
 * - `attachMany`
 * - `hasOne`
 * - `hasMany`
 * - `morphOne`
 * - `morphMany`
 *
 * This trait provides a declarative way to mark a relationship as dependent, by simply adding `->dependent()` to the
 * relationship definition method. For example:
 *
 * ```php
 * public function messages()
 * {
 *     return $this->hasMany(Message::class)->dependent();
 * }
 * ```
 *
 * If you are using the array-style definition, you can use the `delete` key to mark the relationship as dependent.
 *
 * ```php
 * public $hasMany = [
 *     'messages' => [Message::class, 'delete' => true]
 * ];
 * ```
 *
 * @author Ben Thomson <git@alfreido.com>
 * @copyright Winter CMS Maintainers
 */
trait CanBeDependent
{
    /**
     * Is this relation dependent on the primary model?
     */
    protected bool $dependent = false;

    /**
     * Mark the relationship as dependent on the primary model.
     */
    public function dependent(bool $enabled = true): static
    {
        $this->dependent = $enabled;

        return $this;
    }

    /**
     * Mark the relationship as independent of the primary model.
     */
    public function notDependent(): static
    {
        $this->dependent = false;

        return $this;
    }

    /**
     * Determine if the relationship is dependent on the primary model.
     */
    public function isDependent(): bool
    {
        return $this->dependent;
    }
}
