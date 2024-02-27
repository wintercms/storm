<?php

namespace Winter\Storm\Tests\Database\Fixtures;

class UserLaravelWithSoftDelete extends UserLaravel
{
    use \Winter\Storm\Database\Traits\SoftDelete;
}
