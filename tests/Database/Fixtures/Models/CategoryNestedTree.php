<?php

namespace Winter\Storm\Tests\Database\Fixtures\Models;

class CategoryNestedTree extends Category
{
    use \Winter\Storm\Database\Traits\NestedTree;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_categories_nested';
}
