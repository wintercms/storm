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
        $this->assertEquals(2019, $records->first()->start_year);
        $this->assertEquals('Maintainer', $records->last()->role);
        $this->assertEquals(2021, $records->last()->start_year);
    }

    public function testGet(): void
    {
        $record = ArrayModel::find(2);

        $this->assertEquals('Luke Towers', $record->name);
        $this->assertEquals('Lead Maintainer', $record->role);
    }

    public function testWhere(): void
    {
        $records = ArrayModel::where('role', 'Maintainer');

        $this->assertEquals(3, $records->count());
        $this->assertEquals([
            'Ben Thomson',
            'Marc Jauvin',
            'Jack Wilkinson',
        ], $records->pluck('name')->toArray());
    }

    public function testOrder(): void
    {
        $records = ArrayModel::orderBy('name');

        $this->assertEquals(4, $records->count());
        $this->assertEquals([
            'Ben Thomson',
            'Jack Wilkinson',
            'Luke Towers',
            'Marc Jauvin',
        ], $records->pluck('name')->toArray());
    }

    public function testLimit(): void
    {
        $records = ArrayModel::limit(2)->get();

        $this->assertEquals(2, $records->count());
        $this->assertEquals([
            'Ben Thomson',
            'Luke Towers',
        ], $records->pluck('name')->toArray());
    }

    public function testRelations(): void
    {
        $records = Country::get();

        $this->assertEquals(2, $records->count());
        $this->assertEquals(8, $records->first()->states()->count()); // Australia
        $this->assertEquals(10, $records->last()->states()->count()); // Canada

        $this->assertEquals(1, $records->first()->states()->first()->id);
        $this->assertEquals('Western Australia', $records->first()->states()->first()->name);

        $this->assertEquals(18, $records->last()->states()->get()->last()->id);
        $this->assertEquals('Newfoundland and Labrador', $records->last()->states()->get()->last()->name);
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
            'start_year' => '2019',
        ],
        [
            'id' => 2,
            'name' => 'Luke Towers',
            'role' => 'Lead Maintainer',
            'start_year' => '2016',
        ],
        [
            'id' => 3,
            'name' => 'Marc Jauvin',
            'role' => 'Maintainer',
            'start_year' => '2019',
        ],
        [
            'id' => 4,
            'name' => 'Jack Wilkinson',
            'role' => 'Maintainer',
            'start_year' => '2021',
        ],
    ];

    public $arraySchema = [
        'start_year' => 'integer',
    ];

    protected function getArrayDbDir(): string|false
    {
        return dirname(dirname(__DIR__)) . '/tmp';
    }
}

class Country extends \Winter\Storm\Database\Model
{
    use \Winter\Storm\Database\Traits\ArraySource;

    public $records = [
        [
            'id' => 1,
            'name' => 'Australia',
        ],
        [
            'id' => 2,
            'name' => 'Canada',
        ],
    ];

    public $hasMany = [
        'states' => State::class,
    ];

    protected function getArrayDbDir(): string|false
    {
        return dirname(dirname(__DIR__)) . '/tmp';
    }
}

class State extends \Winter\Storm\Database\Model
{
    use \Winter\Storm\Database\Traits\ArraySource;

    public $records = [
        [
            'country_id' => 1,
            'name' => 'Western Australia',
        ],
        [
            'country_id' => 1,
            'name' => 'South Australia',
        ],
        [
            'country_id' => 1,
            'name' => 'Victoria',
        ],
        [
            'country_id' => 1,
            'name' => 'Australian Capital Territory',
        ],
        [
            'country_id' => 1,
            'name' => 'New South Wales',
        ],
        [
            'country_id' => 1,
            'name' => 'Queensland',
        ],
        [
            'country_id' => 1,
            'name' => 'Northern Territory',
        ],
        [
            'country_id' => 1,
            'name' => 'Tasmania',
        ],
        [
            'country_id' => 2,
            'name' => 'Ontario',
        ],
        [
            'country_id' => 2,
            'name' => 'Quebec',
        ],
        [
            'country_id' => 2,
            'name' => 'Nova Scotia',
        ],
        [
            'country_id' => 2,
            'name' => 'New Brunswick',
        ],
        [
            'country_id' => 2,
            'name' => 'Manitoba',
        ],
        [
            'country_id' => 2,
            'name' => 'British Columbia',
        ],
        [
            'country_id' => 2,
            'name' => 'Prince Edward Island',
        ],
        [
            'country_id' => 2,
            'name' => 'Saskatchewan',
        ],
        [
            'country_id' => 2,
            'name' => 'Alberta',
        ],
        [
            'country_id' => 2,
            'name' => 'Newfoundland and Labrador',
        ],
    ];

    public $belongsTo = [
        'country' => Country::class,
    ];

    protected function getArrayDbDir(): string|false
    {
        return dirname(dirname(__DIR__)) . '/tmp';
    }
}
