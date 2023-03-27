<?php

namespace Winter\Storm\Tests\Database\Fixtures\Models;

class SoftDeleteUser extends User
{
    use \Winter\Storm\Database\Traits\SoftDelete;
}
