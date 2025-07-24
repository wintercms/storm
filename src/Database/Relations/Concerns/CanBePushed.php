<?php

namespace Winter\Storm\Database\Relations\Concerns;

/**
 * This trait is used to mark certain relationships as pushable.
 *
 * A pushable relationship will be saved when `push()` is called on the primary model, signalling that the primary model
 * and all related models should be saved. In certain circumstances, you may wish to withhold saving related models
 * when this occurs.
 *
 * This trait provides a declarative way to mark a relationship as pushable or not, by simply adding `->push()` or
 * `->noPush()` to the relationship definition method. For example:
 *
 * ```php
 * public function messages()
 * {
 *     return $this->hasMany(Message::class)->push();
 * }
 * ```
 *
 * If you are using the array-style definition, you can use the `push` key to mark the relationship as pushable or not.
 *
 * ```php
 * public $hasMany = [
 *     'messages' => [Message::class, 'push' => true]
 * ];
 * ```
 *
 * Please note that by default, all relationships are pushable.
 *
 * @author Ben Thomson <git@alfreido.com>
 * @copyright Winter CMS Maintainers
 */
trait CanBePushed
{
    /**
     * Is this relation pushable?
     */
    protected bool $isPushable = true;

    /**
     * Allow this relationship to be saved when the `push()` method is used on the primary model.
     */
    public function pushable(bool $enabled = true): static
    {
        $this->isPushable = $enabled;

        return $this;
    }

    /**
     * Disallow this relationship from being saved when the `push()` method is used on the primary model.
     */
    public function notPushable(): static
    {
        $this->isPushable = false;

        return $this;
    }

    /**
     * Determine if the relationship is pushable.
     */
    public function isPushable(): bool
    {
        return $this->isPushable;
    }
}
