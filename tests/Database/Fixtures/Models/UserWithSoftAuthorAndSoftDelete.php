<?php

namespace Winter\Storm\Tests\Database\Fixtures\Models;

class UserWithSoftAuthorAndSoftDelete extends UserWithSoftAuthor
{
    use \Winter\Storm\Database\Traits\SoftDelete;
}
