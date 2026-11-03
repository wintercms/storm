<?php

namespace Winter\Storm\Tests\Halcyon;

use Winter\Storm\Filesystem\Filesystem;
use Winter\Storm\Halcyon\Datasource\FileDatasource;
use Winter\Storm\Halcyon\Exception\FileExistsException;
use Winter\Storm\Halcyon\Exception\InvalidFileNameException;
use Winter\Storm\Halcyon\Processors\Processor;
use Winter\Storm\Tests\TestCase;

class FileDatasourceTest extends TestCase
{
    /**
     * @var string Scratch directory for this datasource
     */
    protected $basePath;

    /**
     * @var FileDatasource
     */
    protected $datasource;

    /**
     * @var Filesystem
     */
    protected $files;

    public function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->basePath = __DIR__ . '/../tmp/filedatasource';

        $this->files->deleteDirectory($this->basePath);

        $this->seedFile('pages/home.htm', 'Home page');
        $this->seedFile('pages/about.htm', 'About page');
        $this->seedFile('pages/nested/deep.htm', 'Deep page');
        $this->seedFile('pages/notes.md', 'Some notes');
        $this->seedFile('content/welcome.md', 'Welcome');

        // Fixed timestamps keep the mtime assertions deterministic
        touch($this->basePath . '/pages/home.htm', strtotime('2019-06-01 12:00:00'));

        $this->datasource = new FileDatasource($this->basePath, $this->files);
    }

    public function tearDown(): void
    {
        $this->files->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    protected function seedFile(string $path, string $content): void
    {
        $full = $this->basePath . '/' . $path;

        $this->files->makeDirectory(dirname($full), 0777, true, true);
        $this->files->put($full, $content);
    }

    //
    // getAvailablePaths()
    //

    public function testGetAvailablePathsReturnsTrueForEveryPath()
    {
        $paths = $this->datasource->getAvailablePaths();

        $this->assertNotEmpty($paths);

        // Deliberately `true` rather than a modification time. DbDatasource reports
        // timestamps so consumers can skip a database round trip, but resolving a file's
        // mtime is a cheap local stat and must stay live -- baking it into the paths cache
        // would mean template edits on disk (a deploy, for instance) are not picked up
        // until that forever-cached manifest is rebuilt.
        foreach ($paths as $path => $value) {
            $this->assertTrue($value, "Expected true for {$path}");
        }
    }

    public function testGetAvailablePathsListsEveryFileRecursively()
    {
        $paths = $this->datasource->getAvailablePaths();

        $this->assertEqualsCanonicalizing([
            'pages/home.htm',
            'pages/about.htm',
            'pages/nested/deep.htm',
            'pages/notes.md',
            'content/welcome.md',
        ], array_keys($paths));
    }

    public function testGetAvailablePathsIsEmptyWhenBasePathIsMissing()
    {
        $datasource = new FileDatasource($this->basePath . '/nope', $this->files);

        $this->assertSame([], $datasource->getAvailablePaths());
    }

    //
    // selectOne()
    //

    public function testSelectOneReturnsContentAndMtime()
    {
        $result = $this->datasource->selectOne('pages', 'home', 'htm');

        $this->assertSame('home.htm', $result['fileName']);
        $this->assertSame('Home page', $result['content']);
        $this->assertSame(strtotime('2019-06-01 12:00:00'), $result['mtime']);
    }

    public function testSelectOneReadsNestedFiles()
    {
        $result = $this->datasource->selectOne('pages', 'nested/deep', 'htm');

        $this->assertSame('Deep page', $result['content']);
    }

    public function testSelectOneReturnsNullForMissingFile()
    {
        $this->assertNull($this->datasource->selectOne('pages', 'nope', 'htm'));
    }

    //
    // select()
    //

    public function testSelectReturnsEveryFileInTheDirectory()
    {
        $results = collect($this->datasource->select('pages'))->keyBy('fileName');

        $this->assertEqualsCanonicalizing(
            ['home.htm', 'about.htm', 'notes.md', 'nested/deep.htm'],
            $results->keys()->all()
        );
        $this->assertSame('Home page', $results['home.htm']['content']);
        $this->assertSame(strtotime('2019-06-01 12:00:00'), $results['home.htm']['mtime']);
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

        $this->assertEqualsCanonicalizing(['fileName', 'content', 'mtime'], array_keys($results[0]));
    }

    public function testSelectReturnsEmptyForMissingDirectory()
    {
        $this->assertSame([], $this->datasource->select('nope'));
    }

    //
    // insert()
    //

    public function testInsertCreatesTheFileAndReturnsItsSize()
    {
        $size = $this->datasource->insert('pages', 'created', 'htm', 'Created page');

        $this->assertSame(12, $size);
        $this->assertSame('Created page', $this->files->get($this->basePath . '/pages/created.htm'));
    }

    public function testInsertCreatesMissingDirectories()
    {
        $this->datasource->insert('layouts', 'sub/created', 'htm', 'Created layout');

        $this->assertSame('Created layout', $this->files->get($this->basePath . '/layouts/sub/created.htm'));
    }

    public function testInsertThrowsWhenTheFileAlreadyExists()
    {
        $this->expectException(FileExistsException::class);

        $this->datasource->insert('pages', 'home', 'htm', 'Replacement');
    }

    //
    // update()
    //

    public function testUpdateOverwritesContentAndReturnsItsSize()
    {
        $size = $this->datasource->update('pages', 'home', 'htm', 'Updated page');

        $this->assertSame(12, $size);
        $this->assertSame('Updated page', $this->datasource->selectOne('pages', 'home', 'htm')['content']);
    }

    public function testUpdateRenamesTheFile()
    {
        $this->datasource->update('pages', 'renamed', 'htm', 'Renamed page', 'home', 'htm');

        $this->assertNull($this->datasource->selectOne('pages', 'home', 'htm'));
        $this->assertSame('Renamed page', $this->datasource->selectOne('pages', 'renamed', 'htm')['content']);
    }

    public function testUpdateChangesTheExtension()
    {
        $this->datasource->update('pages', 'home', 'md', 'Now markdown', 'home', 'htm');

        $this->assertNull($this->datasource->selectOne('pages', 'home', 'htm'));
        $this->assertSame('Now markdown', $this->datasource->selectOne('pages', 'home', 'md')['content']);
    }

    public function testUpdateAllowsRenamingWhenOnlyTheCaseChanges()
    {
        $this->datasource->update('pages', 'Home', 'htm', 'Recased page', 'home', 'htm');

        $this->assertSame('Recased page', $this->datasource->selectOne('pages', 'Home', 'htm')['content']);
    }

    public function testUpdateThrowsWhenRenamingOntoAnExistingFile()
    {
        $this->expectException(FileExistsException::class);

        $this->datasource->update('pages', 'about', 'htm', 'Clobbered', 'home', 'htm');
    }

    //
    // delete()
    //

    public function testDeleteRemovesTheFile()
    {
        $this->assertTrue($this->datasource->delete('pages', 'home', 'htm'));
        $this->assertNull($this->datasource->selectOne('pages', 'home', 'htm'));
    }

    public function testForceDeleteRemovesTheFile()
    {
        $this->assertTrue($this->datasource->forceDelete('pages', 'home', 'htm'));
        $this->assertNull($this->datasource->selectOne('pages', 'home', 'htm'));
    }

    public function testDeleteReturnsFalseForMissingFile()
    {
        $this->assertFalse($this->datasource->delete('pages', 'nope', 'htm'));
    }

    //
    // lastModified()
    //

    public function testLastModifiedReturnsTheFileMtime()
    {
        $this->assertSame(
            strtotime('2019-06-01 12:00:00'),
            $this->datasource->lastModified('pages', 'home', 'htm')
        );
    }

    public function testLastModifiedReturnsNullForMissingFile()
    {
        $this->assertNull($this->datasource->lastModified('pages', 'nope', 'htm'));
    }

    public function testLastModifiedTracksChangesOnDisk()
    {
        $before = $this->datasource->lastModified('pages', 'home', 'htm');

        touch($this->basePath . '/pages/home.htm', strtotime('2020-01-01 12:00:00'));

        // Filesystem mtimes are resolved live, so edits on disk are picked up immediately
        $this->assertNotSame($before, $this->datasource->lastModified('pages', 'home', 'htm'));
    }

    //
    // Path handling
    //

    public function testReadingPathsOutsideTheBasePathReturnsNothing()
    {
        // makeDirectoryPath() throws for paths that escape the base path, but selectOne()
        // swallows it along with every other read error, so traversal is denied quietly
        $this->assertNull($this->datasource->selectOne('pages', '../../../etc/passwd', 'htm'));
        $this->assertNull($this->datasource->lastModified('pages', '../../../etc/passwd', 'htm'));
    }

    public function testInsertRejectsPathsOutsideTheBasePath()
    {
        $this->expectException(InvalidFileNameException::class);

        $this->datasource->insert('pages', '../escaped', 'htm', 'Nope');
    }

    //
    // Misc
    //

    public function testGetBasePath()
    {
        $this->assertSame($this->basePath, $this->datasource->getBasePath());
    }

    public function testGetPathsCacheKeyIsScopedToTheBasePath()
    {
        $this->assertSame('halcyon-datastore-file-' . $this->basePath, $this->datasource->getPathsCacheKey());

        $other = new FileDatasource($this->basePath . '/other', $this->files);
        $this->assertNotSame($this->datasource->getPathsCacheKey(), $other->getPathsCacheKey());
    }

    public function testMakeCacheKeyIsDeterministic()
    {
        $this->assertSame(
            $this->datasource->makeCacheKey('pages/home.htm'),
            $this->datasource->makeCacheKey('pages/home.htm')
        );
        $this->assertNotSame(
            $this->datasource->makeCacheKey('pages/home.htm'),
            $this->datasource->makeCacheKey('pages/about.htm')
        );
    }

    public function testGetPostProcessor()
    {
        $this->assertInstanceOf(Processor::class, $this->datasource->getPostProcessor());
    }
}
