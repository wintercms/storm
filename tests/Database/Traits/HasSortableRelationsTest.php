<?php

class HasSortableRelationsTest extends TestCase
{
    public function testForInfiniteLoop()
    {
        $model = new TestModel();
        $this->assertTrue($model instanceof TestModel);
    }
}

/*
* Class with HasSortableRelations trait
*/
class TestModel extends \Winter\Storm\Database\Model
{
    use \Winter\Storm\Database\Traits\HasSortableRelations;

    protected $sortableRelations = [
        'relationToSelf' => 'sort_order',
    ];

    public $belongsToMany = [
        'relationToSelf' => TestModel::class,
    ];
}
