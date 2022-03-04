<?php

use Illuminate\Filesystem\Filesystem;

class ArraySourceTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->tmpDbPath = dirname(dirname(__DIR__)) . '/tmp';
        $this->file = new Filesystem();

        // Create temp directory for SQLite DBs
        $this->file->deleteDirectory($this->tmpDbPath);
        $this->file->makeDirectory($this->tmpDbPath, 0755, true);
    }

    public function tearDown(): void
    {
        $this->file->deleteDirectory($this->tmpDbPath);

        parent::tearDown();
    }

    public function testAll(): void
    {
        $records = ArrayModel::get();

        $this->assertEquals(4, $records->count());
        $this->assertEquals('Ben Thomson', $records->first()->name);
    }
}

class ArrayModel extends \Winter\Storm\Database\Model
{
    use \Winter\Storm\Database\Traits\ArraySource;

    public $records = [
        [
            'id' => 1,
            'name' => 'Ben Thomson',
            'role' => 'Maintainer',
        ],
        [
            'id' => 2,
            'name' => 'Luke Towers',
            'role' => 'Lead Maintainer',
        ],
        [
            'id' => 3,
            'name' => 'Marc Jauvin',
            'role' => 'Maintainer',
        ],
        [
            'id' => 4,
            'name' => 'Jack Wilkinson',
            'role' => 'Maintainer',
        ],
    ];

    protected function getArrayDbDir(): string|false
    {
        return dirname(dirname(__DIR__)) . '/tmp';
    }
}
