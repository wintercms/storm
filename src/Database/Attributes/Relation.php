<?php

namespace Winter\Storm\Database\Attributes;

use Attribute;
use Winter\Storm\Database\Relations\AttachMany;
use Winter\Storm\Database\Relations\AttachOne;
use Winter\Storm\Database\Relations\BelongsTo;
use Winter\Storm\Database\Relations\BelongsToMany;
use Winter\Storm\Database\Relations\HasMany;
use Winter\Storm\Database\Relations\HasManyThrough;
use Winter\Storm\Database\Relations\HasOne;
use Winter\Storm\Database\Relations\HasOneThrough;
use Winter\Storm\Database\Relations\MorphMany;
use Winter\Storm\Database\Relations\MorphOne;
use Winter\Storm\Database\Relations\MorphTo;
use Winter\Storm\Database\Relations\MorphToMany;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
class Relation
{
    private string $type;

    public static array $relationTypes = [
        'hasOne' => HasOne::class,
        'hasMany' => HasMany::class,
        'belongsTo' => BelongsTo::class,
        'belongsToMany' => BelongsToMany::class,
        'morphTo' => MorphTo::class,
        'morphOne' => MorphOne::class,
        'morphMany' => MorphMany::class,
        'morphToMany' => MorphToMany::class,
        'morphedByMany' => MorphToMany::class,
        'attachOne' => AttachOne::class,
        'attachMany' => AttachMany::class,
        'hasOneThrough' => HasOneThrough::class,
        'hasManyThrough' => HasManyThrough::class,
    ];

    public function __construct(string $type)
    {
        if (in_array($type, array_keys(static::$relationTypes))) {
            $this->type = $type;
        } elseif (in_array($type, array_values(static::$relationTypes))) {
            $this->type = array_search($type, static::$relationTypes);
        } else {
            throw new \Exception('Invalid relation type');
        }
    }

    public function getType(): string
    {
        return $this->type;
    }
}
