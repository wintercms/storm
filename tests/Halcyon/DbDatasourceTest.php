<?php

namespace Winter\Storm\Tests\Halcyon;

use Illuminate\Support\Facades\DB;
use Winter\Storm\Halcyon\Datasource\DbDatasource;
use Winter\Storm\Halcyon\Exception\DeleteFileException;
use Winter\Storm\Halcyon\Exception\FileExistsException;
use Winter\Storm\Halcyon\Processors\Processor;
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
                'path' => 'pages/about.htm',
                'content' => 'About page',
                'file_size' => 10,
                'updated_at' => '2019-06-05 12:00:00',
                'deleted_at' => null,
            ],
            [
                'source' => 'test',
                'path' => 'pages/notes.md',
                'content' => 'Some notes',
                'file_size' => 10,
                'updated_at' => '2019-06-06 12:00:00',
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

    /**
     * Reads a row straight out of the table, bypassing the datasource.
     */
    protected function rawRecord(string $path, string $source = 'test')
    {
        return DB::table(self::TABLE)->where('source', $source)->where('path', $path)->first();
    }

    //
    // getAvailablePaths()
    //

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

    public function testGetAvailablePathsFallsBackToTrueForTheEpoch()
    {
        DB::table(self::TABLE)->insert([
            'source' => 'test',
            'path' => 'pages/epoch.htm',
            'content' => 'Epoch page',
            'file_size' => 10,
            'updated_at' => '1970-01-01 00:00:00',
            'deleted_at' => null,
        ]);

        $paths = $this->datasource->getAvailablePaths();

        // Consumers of this map test the value for truthiness, so a timestamp of 0 would
        // make a live record read as deleted
        $this->assertTrue($paths['pages/epoch.htm']);
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

    //
    // selectOne()
    //

    public function testSelectOneReturnsTheRecord()
    {
        $result = $this->datasource->selectOne('pages', 'index', 'htm');

        $this->assertSame('index.htm', $result['fileName']);
        $this->assertSame('Index page', $result['content']);
        $this->assertSame(strtotime('2019-06-01 12:00:00'), $result['mtime']);
        $this->assertSame('pages/index.htm', $result['record']->path);
    }

    public function testSelectOneReturnsNullForMissingRecord()
    {
        $this->assertNull($this->datasource->selectOne('pages', 'nope', 'htm'));
    }

    public function testSelectOneIgnoresDeletedRecords()
    {
        $this->assertNull($this->datasource->selectOne('pages', 'deleted', 'htm'));
    }

    public function testSelectOneIsScopedToTheSource()
    {
        $this->assertNull($this->datasource->selectOne('pages', 'other', 'htm'));
    }

    //
    // select()
    //

    public function testSelectReturnsEveryLiveRecordInTheDirectory()
    {
        $results = collect($this->datasource->select('pages'))->keyBy('fileName');

        $this->assertEqualsCanonicalizing(
            ['index.htm', 'about.htm', 'notes.md'],
            $results->keys()->all()
        );
        $this->assertSame('Index page', $results['index.htm']['content']);
        $this->assertSame(strtotime('2019-06-01 12:00:00'), $results['index.htm']['mtime']);
    }

    public function testSelectExcludesDeletedRecords()
    {
        $results = collect($this->datasource->select('pages'))->pluck('fileName');

        $this->assertNotContains('deleted.htm', $results->all());
    }

    public function testSelectIsScopedToTheSource()
    {
        $results = collect($this->datasource->select('pages'))->pluck('fileName');

        $this->assertNotContains('other.htm', $results->all());
    }

    public function testSelectFiltersByExtension()
    {
        $results = collect($this->datasource->select('pages', ['extensions' => ['md']]));

        $this->assertSame(['notes.md'], $results->pluck('fileName')->all());
    }

    public function testSelectFiltersByFileMatch()
    {
        $results = collect($this->datasource->select('pages', ['fileMatch' => 'ab*']));

        $this->assertSame(['about.htm'], $results->pluck('fileName')->all());
    }

    public function testSelectLimitsReturnedColumns()
    {
        $results = $this->datasource->select('pages', ['columns' => ['fileName']]);

        $this->assertSame(['fileName'], array_keys($results[0]));
    }

    public function testSelectTreatsWildcardColumnsAsAllColumns()
    {
        $results = $this->datasource->select('pages', ['columns' => ['*']]);

        $this->assertEqualsCanonicalizing(
            ['fileName', 'content', 'mtime', 'record'],
            array_keys($results[0])
        );
    }

    //
    // insert()
    //

    public function testInsertCreatesTheRecordAndReturnsItsSize()
    {
        $size = $this->datasource->insert('pages', 'created', 'htm', 'Created page');

        $this->assertSame(12, $size);

        $record = $this->rawRecord('pages/created.htm');
        $this->assertSame('Created page', $record->content);
        $this->assertSame(12, (int) $record->file_size);
        $this->assertNotNull($record->updated_at);
        $this->assertNull($record->deleted_at);
    }

    public function testInsertThrowsWhenThePathAlreadyExists()
    {
        $this->expectException(FileExistsException::class);

        $this->datasource->insert('pages', 'index', 'htm', 'Replacement');
    }

    public function testInsertRevivesASoftDeletedRecord()
    {
        $this->datasource->insert('pages', 'deleted', 'htm', 'Revived page');

        $record = $this->rawRecord('pages/deleted.htm');
        $this->assertSame('Revived page', $record->content);
        $this->assertNull($record->deleted_at);

        // Revived in place rather than duplicated
        $this->assertSame(1, DB::table(self::TABLE)->where('path', 'pages/deleted.htm')->count());
    }

    public function testInsertFiresTheBeforeInsertEvent()
    {
        $this->datasource->bindEvent('halcyon.datasource.db.beforeInsert', function (&$record) {
            $record['content'] = 'Rewritten by the event';
        });

        $this->datasource->insert('pages', 'created', 'htm', 'Created page');

        $this->assertSame('Rewritten by the event', $this->rawRecord('pages/created.htm')->content);
    }

    //
    // update()
    //

    public function testUpdateChangesContentAndTimestamp()
    {
        $size = $this->datasource->update('pages', 'index', 'htm', 'Updated page');

        $this->assertSame(12, $size);

        $record = $this->rawRecord('pages/index.htm');
        $this->assertSame('Updated page', $record->content);
        $this->assertGreaterThan(
            strtotime('2019-06-01 12:00:00'),
            strtotime($record->updated_at)
        );
    }

    public function testUpdateRenamesTheRecord()
    {
        $this->datasource->update('pages', 'renamed', 'htm', 'Renamed page', 'index', 'htm');

        $this->assertNull($this->rawRecord('pages/index.htm'));
        $this->assertSame('Renamed page', $this->rawRecord('pages/renamed.htm')->content);
    }

    public function testUpdateChangesTheExtension()
    {
        $this->datasource->update('pages', 'index', 'md', 'Now markdown', 'index', 'htm');

        $this->assertNull($this->rawRecord('pages/index.htm'));
        $this->assertSame('Now markdown', $this->rawRecord('pages/index.md')->content);
    }

    public function testUpdateClearsTheDeletedFlag()
    {
        $this->datasource->update('pages', 'deleted', 'htm', 'Restored page');

        $this->assertNull($this->rawRecord('pages/deleted.htm')->deleted_at);
    }

    public function testUpdateFiresTheBeforeUpdateEvent()
    {
        $this->datasource->bindEvent('halcyon.datasource.db.beforeUpdate', function (&$data) {
            $data['content'] = 'Rewritten by the event';
        });

        $this->datasource->update('pages', 'index', 'htm', 'Updated page');

        $this->assertSame('Rewritten by the event', $this->rawRecord('pages/index.htm')->content);
    }

    //
    // delete()
    //

    public function testDeleteSoftDeletesTheRecord()
    {
        $this->assertTrue($this->datasource->delete('pages', 'index', 'htm'));

        $record = $this->rawRecord('pages/index.htm');
        $this->assertNotNull($record, 'The row should be retained');
        $this->assertNotNull($record->deleted_at);
        $this->assertNull($this->datasource->selectOne('pages', 'index', 'htm'));
    }

    public function testDeleteThrowsWhenNoRecordMatches()
    {
        $this->expectException(DeleteFileException::class);

        $this->datasource->delete('pages', 'nope', 'htm');
    }

    public function testDeleteThrowsForAnAlreadyDeletedRecord()
    {
        $this->expectException(DeleteFileException::class);

        $this->datasource->delete('pages', 'deleted', 'htm');
    }

    public function testForceDeleteRemovesTheRecord()
    {
        $this->assertTrue($this->datasource->forceDelete('pages', 'index', 'htm'));

        $this->assertNull($this->rawRecord('pages/index.htm'));
    }

    //
    // lastModified()
    //

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

    public function testLastModifiedIgnoresDeletedRecords()
    {
        $this->assertNull($this->datasource->lastModified('pages', 'deleted', 'htm'));
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

    //
    // Misc
    //

    public function testExtendQueryEventAppliesToReads()
    {
        $this->datasource->bindEvent('halcyon.datasource.db.extendQuery', function ($query) {
            $query->where('path', 'pages/about.htm');
        });

        $results = collect($this->datasource->select('pages'))->pluck('fileName');

        $this->assertSame(['about.htm'], $results->all());
    }

    public function testGetPathsCacheKeyIsVersionedAndScoped()
    {
        $this->assertSame(
            'halcyon-datastore-db-v2-' . self::TABLE . '-test',
            $this->datasource->getPathsCacheKey()
        );

        // The payload shape changed from booleans to timestamps, so the key must not
        // collide with manifests written by earlier versions
        $this->assertStringNotContainsString(
            'halcyon-datastore-db-' . self::TABLE,
            $this->datasource->getPathsCacheKey()
        );

        $other = new DbDatasource('other', self::TABLE);
        $this->assertNotSame($this->datasource->getPathsCacheKey(), $other->getPathsCacheKey());
    }

    public function testMakeCacheKeyIsDeterministic()
    {
        $this->assertSame(
            $this->datasource->makeCacheKey('pages/index.htm'),
            $this->datasource->makeCacheKey('pages/index.htm')
        );
        $this->assertNotSame(
            $this->datasource->makeCacheKey('pages/index.htm'),
            $this->datasource->makeCacheKey('pages/about.htm')
        );
    }

    public function testGetPostProcessor()
    {
        $this->assertInstanceOf(Processor::class, $this->datasource->getPostProcessor());
    }
}
