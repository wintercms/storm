<?php

use Winter\Storm\Database\Model;

class ModelTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->createTable();
    }

    public function testAddCasts()
    {
        $model = new TestModelGuarded();

        $this->assertEquals(['id' => 'int'], $model->getCasts());

        $model->addCasts(['foo' => 'int']);

        $this->assertEquals(['id' => 'int', 'foo' => 'int'], $model->getCasts());
    }

    public function testStringIsTrimmed()
    {
        $name = "Name";
        $nameWithSpace = "  {$name}  ";
        $model = new TestModelGuarded();

        $model->name = $nameWithSpace;
        $model->save();

        // Make sure we load the database saved model
        $model->refresh();
        $this->assertEquals($name, $model->name);

        $model->trimStringAttributes = false;
        $model->name = $nameWithSpace;
        $model->save();

        // Refresh the model from the database
        $model->refresh();
        $this->assertEquals($nameWithSpace, $model->name);
    }

    public function testIsGuarded()
    {
        $model = new TestModelGuarded();

        // Test base guarded property
        $this->assertTrue($model->isGuarded('data'));

        // Test variations on casing
        $this->assertTrue($model->isGuarded('DATA'));
        $this->assertTrue($model->isGuarded('name'));

        // Test JSON columns
        $this->assertTrue($model->isGuarded('data->key'));
    }

    public function testMassAssignmentOnFieldsNotInDatabase()
    {
        $model = TestModelGuarded::create([
            'name' => 'Guard Test',
            'data' => 'Test data',
            'is_guarded' => true
        ]);

        $this->assertTrue($model->on_guard); // Guarded property, set by "is_guarded"
        $this->assertNull($model->name); // Guarded property
        $this->assertNull($model->is_guarded); // Non-guarded, non-existent property

        $model = TestModelGuarded::create([
            'name' => 'Guard Test',
            'data' => 'Test data',
            'is_guarded' => false
        ]);

        $this->assertFalse($model->on_guard);
        $this->assertNull($model->name);
        $this->assertNull($model->is_guarded);

        $model = TestModelGuarded::create([
            'name' => 'Guard Test',
            'data' => 'Test data'
        ]);

        $this->assertNull($model->on_guard);
        $this->assertNull($model->name);

        // Check that we cannot mass-fill the "on_guard" property
        $model = TestModelGuarded::create([
            'name' => 'Guard Test',
            'data' => 'Test data',
            'on_guard' => true
        ]);

        $this->assertNull($model->on_guard);
        $this->assertNull($model->name);
    }

    public function testVisibleAttributes()
    {
        $model = TestModelVisible::create([
            'name' => 'Visible Test',
            'data' => 'Test data',
            'description' => 'Test description',
            'meta' => 'Some meta data'
        ]);

        $this->assertArrayNotHasKey('meta', $model->toArray());

        $model->addVisible('meta');

        $this->assertArrayHasKey('meta', $model->toArray());
    }

    public function testHiddenAttributes()
    {
        $model = TestModelHidden::create([
            'name' => 'Hidden Test',
            'data' => 'Test data',
            'description' => 'Test description',
            'meta' => 'Some meta data'
        ]);

        $this->assertArrayHasKey('description', $model->toArray());

        $model->addHidden('description');

        $this->assertArrayNotHasKey('description', $model->toArray());
    }

    protected function createTable()
    {
        $this->getBuilder()->create('test_model', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->text('data')->nullable();
            $table->text('description')->nullable();
            $table->text('meta')->nullable();
            $table->boolean('on_guard')->nullable();
            $table->timestamps();
        });
    }
}

class TestModelGuarded extends Model
{
    protected $guarded = ['id', 'ID', 'NAME', 'data', 'on_guard'];

    public $table = 'test_model';

    public function beforeSave()
    {
        if (!is_null($this->is_guarded)) {
            if ($this->is_guarded === true) {
                $this->on_guard = true;
            } elseif ($this->is_guarded === false) {
                $this->on_guard = false;
            }

            unset($this->is_guarded);
        }
    }
}

class TestModelVisible extends Model
{
    public $fillable = [
        'name',
        'data',
        'description',
        'meta'
    ];

    public $visible = [
        'id',
        'name',
        'description'
    ];

    public $table = 'test_model';
}

class TestModelHidden extends Model
{
    public $fillable = [
        'name',
        'data',
        'description',
        'meta'
    ];

    public $hidden = [
        'meta',
    ];

    public $table = 'test_model';
}
