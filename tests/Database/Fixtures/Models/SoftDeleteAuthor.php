<?php

namespace Winter\Storm\Tests\Database\Fixtures\Models;

class SoftDeleteAuthor extends Author
{
    use \Winter\Storm\Database\Traits\SoftDelete;
}
