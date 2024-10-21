<?php

namespace Winter\Storm\Tests\Database\Fixtures;

class UserWithSoftAuthor extends User
{
    public $hasOne = [
        'author' => [SoftDeleteAuthor::class, 'key' => 'user_id', 'softDelete' => true],
    ];
}
