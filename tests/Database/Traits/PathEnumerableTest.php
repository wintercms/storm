<?php

class PathEnumerableTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->createTable();
    }

    public function testPathsEnumeratedOnCreate()
    {
        $grandparents = new TestModelEnumerablePath([
            'name' => 'Grandparents',
        ]);
        $parents = new TestModelEnumerablePath([
            'name' => 'Parents',
        ]);
        $daughter = new TestModelEnumerablePath([
            'name' => 'Daughter',
        ]);
        $child = new TestModelEnumerablePath([
            'name' => 'Child',
        ]);

        $grandparents->save();
        $this->assertEquals('/1', $grandparents->path);
        $this->assertEquals(0, $grandparents->getDepth());

        $parents->parent = $grandparents;
        $parents->save();
        $this->assertEquals('/1/2', $parents->path);
        $this->assertEquals(1, $parents->getDepth());

        $daughter->parent = $parents;
        $daughter->save();
        $this->assertEquals('/1/2/3', $daughter->path);
        $this->assertEquals(2, $daughter->getDepth());

        $child->parent = $daughter;
        $child->save();
        $this->assertEquals('/1/2/3/4', $child->path);
        $this->assertEquals(3, $child->getDepth());

        // Check hierarchy
        $hierarchy = $child->getParents();
        $this->assertEquals($grandparents->name, $hierarchy->get(0)->name);
        $this->assertEquals($parents->name, $hierarchy->get(1)->name);
        $this->assertEquals($daughter->name, $hierarchy->get(2)->name);

        $root = TestModelEnumerablePath::root()->get();
        $this->assertCount(1, $root);
        $this->assertEquals($grandparents->name, $root->first()->name);
    }

    public function testMoveChildRecordToNewParent()
    {
        $grandparents = new TestModelEnumerablePath([
            'name' => 'Grandparents',
        ]);
        $parents = new TestModelEnumerablePath([
            'name' => 'Parents',
        ]);
        $daughter = new TestModelEnumerablePath([
            'name' => 'Daughter',
        ]);
        $child = new TestModelEnumerablePath([
            'name' => 'Child',
        ]);

        $grandparents->save();
        $parents->parent = $grandparents;
        $parents->save();
        $daughter->parent = $parents;
        $daughter->save();
        $child->parent = $daughter;
        $child->save();

        $this->assertEquals('/1/2/3/4', $child->path);

        // Move child
        $child->parent = $parents;
        $child->save();
        $this->assertEquals('/1/2/4', $child->path);
    }

    public function testMoveChildRecordWithAncestorsToNewParent()
    {
        $grandparents = new TestModelEnumerablePath([
            'name' => 'Grandparents',
        ]);
        $parents = new TestModelEnumerablePath([
            'name' => 'Parents',
        ]);
        $daughter = new TestModelEnumerablePath([
            'name' => 'Daughter',
        ]);
        $child = new TestModelEnumerablePath([
            'name' => 'Child',
        ]);

        $grandparents->save();
        $parents->parent = $grandparents;
        $parents->save();
        $daughter->parent = $parents;
        $daughter->save();
        $child->parent = $daughter;
        $child->save();

        // Move child
        $daughter->parent = $grandparents;
        $daughter->save();
        $this->assertEquals('/1/3', $daughter->path);

        // Get new path for child
        $child = $child->reload();
        $this->assertEquals('/1/3/4', $child->path);
    }

    public function testDeleteRecordWithChildren()
    {
        $grandparents = new TestModelEnumerablePath([
            'name' => 'Grandparents',
        ]);
        $parents = new TestModelEnumerablePath([
            'name' => 'Parents',
        ]);
        $daughter = new TestModelEnumerablePath([
            'name' => 'Daughter',
        ]);
        $child = new TestModelEnumerablePath([
            'name' => 'Child',
        ]);

        $grandparents->save();
        $parents->parent = $grandparents;
        $parents->save();
        $daughter->parent = $parents;
        $daughter->save();
        $child->parent = $daughter;
        $child->save();

        // Delete parents
        $parents->delete();
        $this->assertEquals(1, TestModelEnumerablePath::count());
        $this->assertNull(TestModelEnumerablePath::find($parents->id));
        $this->assertNull(TestModelEnumerablePath::find($daughter->id));
        $this->assertNull(TestModelEnumerablePath::find($child->id));
    }

    public function testGetNestedRecords()
    {
        $grandparents = new TestModelEnumerablePath([
            'name' => 'Grandparents',
        ]);
        $parents = new TestModelEnumerablePath([
            'name' => 'Parents',
        ]);
        $daughter = new TestModelEnumerablePath([
            'name' => 'Daughter',
        ]);
        $child = new TestModelEnumerablePath([
            'name' => 'Child',
        ]);

        $grandparents->save();
        $parents->parent = $grandparents;
        $parents->save();
        $daughter->parent = $parents;
        $daughter->save();
        $child->parent = $daughter;
        $child->save();

        $grandparents->reload();
        $nested = $grandparents->getNested();
        $this->assertEquals($grandparents->name, $nested->get(1)->name);
        $this->assertEquals($parents->name, $nested->get(1)->children->get(0)->name);
        $this->assertEquals($daughter->name, $nested->get(1)->children->get(0)->children->get(0)->name);
        $this->assertEquals($child->name, $nested->get(1)->children->get(0)->children->get(0)->children->get(0)->name);
    }

    public function testPathsEnumeratedOnCreateWithDifferentSegmentColumn()
    {
        $grandparents = new TestModelEnumerablePathNameSegment([
            'name' => 'Grandparents',
        ]);
        $parents = new TestModelEnumerablePathNameSegment([
            'name' => 'Parents',
        ]);
        $daughter = new TestModelEnumerablePathNameSegment([
            'name' => 'Daughter',
        ]);
        $child = new TestModelEnumerablePathNameSegment([
            'name' => 'Child',
        ]);

        $grandparents->save();
        $this->assertEquals('/Grandparents', $grandparents->path);
        $this->assertEquals(0, $grandparents->getDepth());
        $this->assertEquals(0, $grandparents->getParents()->count());

        $parents->parent = $grandparents;
        $parents->save();
        $this->assertEquals('/Grandparents/Parents', $parents->path);
        $this->assertEquals(1, $parents->getDepth());
        $this->assertEquals(1, $parents->getParents()->count());

        $daughter->parent = $parents;
        $daughter->save();
        $this->assertEquals('/Grandparents/Parents/Daughter', $daughter->path);
        $this->assertEquals(2, $daughter->getDepth());
        $this->assertEquals(2, $daughter->getParents()->count());

        $child->parent = $daughter;
        $child->save();
        $this->assertEquals('/Grandparents/Parents/Daughter/Child', $child->path);
        $this->assertEquals(3, $child->getDepth());
        $this->assertEquals(3, $child->getParents()->count());

        // Check hierarchy
        $hierarchy = $child->getParents();
        $this->assertEquals($grandparents->name, $hierarchy->get(0)->name);
        $this->assertEquals($parents->name, $hierarchy->get(1)->name);
        $this->assertEquals($daughter->name, $hierarchy->get(2)->name);

        $root = TestModelEnumerablePathNameSegment::root()->get();
        $this->assertCount(1, $root);
        $this->assertEquals($grandparents->name, $root->first()->name);
    }

    protected function createTable()
    {
        $this->getBuilder()->create('path_enumerable', function ($table) {
            $table->increments('id');
            $table->integer('parent_id')->unsigned()->nullable();
            $table->string('path')->nullable();
            $table->string('name');
            $table->timestamps();
        });
    }
}

class TestModelEnumerablePath extends \Winter\Storm\Database\Model
{
    use \Winter\Storm\Database\Traits\PathEnumerable;

    public $table = 'path_enumerable';
    public $fillable = [
        'name',
    ];
}

class TestModelEnumerablePathNameSegment extends \Winter\Storm\Database\Model
{
    use \Winter\Storm\Database\Traits\PathEnumerable;

    public $table = 'path_enumerable';
    public $fillable = [
        'name',
    ];

    protected string $segmentColumn = 'name';
}
