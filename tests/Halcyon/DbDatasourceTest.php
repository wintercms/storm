<?php

namespace Winter\Storm\Tests\Halcyon;

use Illuminate\Support\Facades\DB;
use Winter\Storm\Halcyon\Datasource\DbDatasource;
use Winter\Storm\Tests\DbTestCase;

class DbDatasourceTest extends DbTestCase
{
    const TABLE = 'halcyon_tester_templates';

    /**
     * @var DbDatasource
     */
    protected $datasource;

    public function setUp(): void
    {
        parent::setUp();

        DB::connection()->getSchemaBuilder()->create(self::TABLE, function ($table) {
            $table->increments('id');
            $table->string('source')->index();
            $table->string('path')->index();
            $table->longText('content');
            $table->integer('file_size')->unsigned();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        DB::table(self::TABLE)->insert([
            [
                'source' => 'test',
                'path' => 'pages/index.htm',
                'content' => 'Index page',
                'file_size' => 10,
                'updated_at' => '2019-06-01 12:00:00',
                'deleted_at' => null,
            ],
            [
                'source' => 'test',
                'path' => 'pages/deleted.htm',
                'content' => 'Deleted page',
                'file_size' => 12,
                'updated_at' => '2019-06-02 12:00:00',
                'deleted_at' => '2019-06-03 12:00:00',
            ],
            [
                'source' => 'other',
                'path' => 'pages/other.htm',
                'content' => 'Other source page',
                'file_size' => 17,
                'updated_at' => '2019-06-04 12:00:00',
                'deleted_at' => null,
            ],
        ]);

        $this->datasource = new DbDatasource('test', self::TABLE);
    }

    public function tearDown(): void
    {
        DB::connection()->getSchemaBuilder()->dropIfExists(self::TABLE);

        parent::tearDown();
    }

    public function testGetAvailablePathsReturnsTimestampsForLiveRecords()
    {
        $paths = $this->datasource->getAvailablePaths();

        $this->assertSame(strtotime('2019-06-01 12:00:00'), $paths['pages/index.htm']);
    }

    public function testGetAvailablePathsReturnsFalseForDeletedRecords()
    {
        $paths = $this->datasource->getAvailablePaths();

        $this->assertFalse($paths['pages/deleted.htm']);
    }

    public function testGetAvailablePathsIsScopedToTheSource()
    {
        $paths = $this->datasource->getAvailablePaths();

        $this->assertArrayNotHasKey('pages/other.htm', $paths);
    }

    public function testGetAvailablePathsHonoursTheBeforeEvent()
    {
        // The documented event contract returns booleans, which must keep working
        $this->datasource->bindEvent('halcyon.datasource.db.beforeGetAvailablePaths', function () {
            return ['pages/from-event.htm' => true, 'pages/gone.htm' => false];
        });

        $this->assertSame(
            ['pages/from-event.htm' => true, 'pages/gone.htm' => false],
            $this->datasource->getAvailablePaths()
        );
    }

    public function testLastModifiedReturnsTheTimestamp()
    {
        $this->assertSame(
            strtotime('2019-06-01 12:00:00'),
            $this->datasource->lastModified('pages', 'index', 'htm')
        );
    }

    public function testLastModifiedReturnsNullForMissingRecord()
    {
        $this->assertNull($this->datasource->lastModified('pages', 'nope', 'htm'));
    }

    public function testLastModifiedDoesNotSelectTheContentColumn()
    {
        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();

        $this->datasource->lastModified('pages', 'index', 'htm');

        $queries = DB::connection()->getQueryLog();
        DB::connection()->disableQueryLog();

        // Selecting the whole row just to read a timestamp drags the template content
        // across the wire on every cache validation
        $this->assertCount(1, $queries);
        $this->assertStringNotContainsString('*', $queries[0]['query']);
        $this->assertStringContainsString('updated_at', $queries[0]['query']);
    }
}
