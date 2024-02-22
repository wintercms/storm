<?php

namespace Winter\Storm\Tests\Database\Fixtures;

class SoftDeleteUser extends User
{
    use \Winter\Storm\Database\Traits\SimpleTree;
}
