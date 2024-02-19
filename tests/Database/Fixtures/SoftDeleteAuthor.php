<?php

namespace Winter\Storm\Tests\Database\Fixtures;

class SoftDeleteAuthor extends Author
{
    use \Winter\Storm\Database\Traits\SimpleTree;
}
