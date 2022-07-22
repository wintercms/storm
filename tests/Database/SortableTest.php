<?php

class SortableTest extends DbTestCase
{
    public function testOrderByIsAutomaticallyAdded()
    {
        $model = new TestSortableModel();
        $query = $model->newQuery()->toSql();

        $this->assertEquals('select * from "test" order by "sort_order" asc', $query);
    }

    public function testCustomSortOrderByIsAutomaticallyAdded()
    {
        $model = new TestCustomSortableModel();
        $query = $model->newQuery()->toSql();

        $this->assertEquals('select * from "test" order by "rank" asc', $query);
    }

    public function testOrderByCanBeOverridden()
    {
        $model = new TestSortableModel();
        $query1 = $model->newQuery()->orderBy('name')->orderBy('email', 'desc')->toSql();
        $query2 = $model->newQuery()->orderBy('sort_order')->orderBy('name')->toSql();

        $this->assertEquals('select * from "test" order by "name" asc, "email" desc', $query1);
        $this->assertEquals('select * from "test" order by "sort_order" asc, "name" asc', $query2);

        $model = new TestCustomSortableModel();
        $query1 = $model->newQuery()->orderBy('name')->orderBy('email', 'desc')->toSql();
        $query2 = $model->newQuery()->orderBy('sort_order')->orderBy('name')->toSql();

        $this->assertEquals('select * from "test" order by "name" asc, "email" desc', $query1);
        $this->assertEquals('select * from "test" order by "sort_order" asc, "name" asc', $query2);
    }
}

class TestSortableModel extends \Winter\Storm\Database\Model
{
    use \Winter\Storm\Database\Traits\Sortable;

    protected $table = 'test';
}

class TestCustomSortableModel extends \Winter\Storm\Database\Model
{
    use \Winter\Storm\Database\Traits\Sortable;

    const SORT_ORDER = 'rank';

    protected $table = 'test';
}
