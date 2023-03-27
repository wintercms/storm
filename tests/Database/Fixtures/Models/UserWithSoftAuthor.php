<?php

namespace Winter\Storm\Tests\Database\Fixtures\Models;

class UserWithSoftAuthor extends User
{
    public $hasOne = [
        'author' => ['Winter\Storm\Tests\Database\Fixtures\Models\SoftDeleteAuthor', 'key' => 'user_id', 'softDelete' => true],
    ];
}
