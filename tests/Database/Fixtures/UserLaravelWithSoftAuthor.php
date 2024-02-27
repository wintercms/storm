<?php

namespace Winter\Storm\Tests\Database\Fixtures;

use Winter\Storm\Database\Relations\HasOne;

class UserLaravelWithSoftAuthor extends UserLaravel
{
    public function author(): HasOne
    {
        return $this->hasOne(SoftDeleteAuthor::class, 'user_id')->softDeletable();
    }
}
