<?php

namespace Winter\Storm\Tests\Database;

use Illuminate\Support\Facades\DB;
use Winter\Storm\Tests\DbTestCase;

class QueryBuilderPaginateTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->getBuilder()->create('pagination_test', function ($table) {
            $table->increments('id');
            $table->integer('group_id');
            $table->string('name');
        });

        DB::table('pagination_test')->insert([
            ['group_id' => 1, 'name' => 'a'],
            ['group_id' => 1, 'name' => 'b'],
            ['group_id' => 2, 'name' => 'c'],
            ['group_id' => 2, 'name' => 'd'],
            ['group_id' => 2, 'name' => 'e'],
            ['group_id' => 3, 'name' => 'f'],
        ]);
    }

    public function tearDown(): void
    {
        $this->getBuilder()->dropIfExists('pagination_test');
        parent::tearDown();
    }

    /**
     * A grouped pagination whose WHERE clause carries bindings must not
     * double-count those bindings when building the pagination count query.
     *
     * Regression test: the QueryBuilder::runPaginationCountQuery() override
     * both passed the count subquery builder to from() (which registers its
     * bindings) and called mergeBindings() on it, binding the WHERE values
     * twice. This produced a SQLSTATE[HY093] "Invalid parameter number" error
     * for any grouped paginate() with bound WHERE conditions.
     */
    public function testGroupedPaginationWithBoundWhere()
    {
        $paginator = DB::table('pagination_test')
            ->whereRaw('(group_id = ? OR group_id > ?)', [1, 0])
            ->groupBy('group_id')
            ->orderBy('group_id')
            ->paginate(2);

        // Three distinct groups => total of 3, spread over two pages of 2.
        $this->assertEquals(3, $paginator->total());
        $this->assertEquals(2, $paginator->lastPage());
        $this->assertCount(2, $paginator->items());
    }

    /**
     * The pagination count override originally existed to add having() support
     * that Laravel's base builder now provides natively. Ensure grouped
     * pagination filtered by a bound having() still returns the correct count.
     */
    public function testGroupedPaginationWithBoundHaving()
    {
        $paginator = DB::table('pagination_test')
            ->select('group_id', DB::raw('count(*) as total'))
            ->groupBy('group_id')
            ->havingRaw('count(*) > ?', [1])
            ->paginate(10);

        // Only groups 1 (2 rows) and 2 (3 rows) qualify; group 3 (1 row) does not.
        $this->assertEquals(2, $paginator->total());
        $this->assertCount(2, $paginator->items());
    }
}
