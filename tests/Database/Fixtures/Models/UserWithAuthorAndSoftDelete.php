<?php

namespace Winter\Storm\Tests\Database\Fixtures\Models;

class UserWithAuthorAndSoftDelete extends UserWithAuthor
{
    use \Winter\Storm\Database\Traits\SoftDelete;
}
