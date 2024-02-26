<?php

namespace Winter\Storm\Tests\Database\Fixtures;

class UserWithSoftAuthorAndSoftDelete extends UserWithSoftAuthor
{
    use \Winter\Storm\Database\Traits\SoftDelete;
}
