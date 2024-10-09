<?php

namespace Winter\Storm\Tests\Database\Fixtures;

class UserLaravelWithSoftAuthorAndSoftDelete extends UserLaravelWithSoftAuthor
{
    use \Winter\Storm\Database\Traits\SoftDelete;
}
