<?php

namespace Winter\Storm\Tests\Database\Fixtures\Models;

class UserWithAuthor extends User
{
    public $hasOne = [
        'author' => ['Winter\Storm\Tests\Database\Fixtures\Models\Author', 'key' => 'user_id', 'delete' => true],
    ];
}
