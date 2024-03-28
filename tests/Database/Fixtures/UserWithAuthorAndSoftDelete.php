<?php

namespace Winter\Storm\Tests\Database\Fixtures;

class UserWithAuthorAndSoftDelete extends UserWithAuthor
{
    use \Winter\Storm\Database\Traits\SoftDelete;
}
