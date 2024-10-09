<?php

namespace Winter\Storm\Database\Relations\Concerns;

use Illuminate\Database\Query\Builder;
use Winter\Storm\Database\Relations\BelongsToMany;
use Winter\Storm\Database\Relations\HasManyThrough;
use Winter\Storm\Database\Relations\HasOneThrough;
use Winter\Storm\Database\Relations\Relation;

/**
 * This trait is used to mark certain relationships as being a counter only.
 *
 * A relationship counter simply returns the number of related records as an integer. For backwards compatibility with
 * the array-style definition, this is returned as an instance of the model, with only a `count` column provided.
 *
 * This trait can be used on all relationship types.
 *
 * This trait provides a declarative way to mark a relationship as a counter only, by simply adding `->countOnly()` to
 * the relationship definition method. For example:
 *
 * ```php
 * public function totalMessages()
 * {
 *     return $this->hasMany(Message::class)->countOnly();
 * }
 * ```
 *
 * If you are using the array-style definition, you can use the `count` key to mark the relationship as a counter only.
 *
 * ```php
 * public $hasMany = [
 *     'total_messages' => [Message::class, 'count' => true]
 * ];
 * ```
 *
 * @author Ben Thomson <git@alfreido.com>
 * @copyright Winter CMS Maintainers
 */
trait CanBeCounted
{
    /**
     * Is this relation a counter?
     */
    protected bool $countOnly = false;

    /**
     * Mark the relationship as a count-only relationship.
     */
    public function countOnly(bool $enabled = true): static
    {
        if (!$enabled) {
            $this->countOnly = false;

            return $this;
        }

        $this->countOnly = true;

        if ($this instanceof BelongsToMany) {
            $this->countMode = true;
        }

        $foreignKey = ($this instanceof HasOneThrough || $this instanceof HasManyThrough)
            ? $this->getQualifiedFirstKeyName()
            : $this->getForeignKey();
        $parent = ($this instanceof HasOneThrough || $this instanceof HasManyThrough)
            ? $this->farParent
            : $this->parent;

        $countSql = $parent->getConnection()->raw('count(*) as count');

        return $this
            ->select($foreignKey, $countSql)
            ->groupBy($foreignKey)
            ->orderBy($foreignKey);
    }

    /**
     * Mark the relationship as a full relationship.
     */
    public function notCountOnly(): static
    {
        $this->countOnly = false;

        return $this;
    }

    /**
     * Determine if the relationship is only a counter.
     */
    public function isCountOnly(): bool
    {
        return $this->countOnly;
    }
}
