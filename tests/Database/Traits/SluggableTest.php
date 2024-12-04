<?php

namespace Winter\Storm\Tests\Database\Traits;

use Winter\Storm\Tests\Database\Fixtures\SluggablePost;
use Winter\Storm\Tests\DbTestCase;

class SluggableTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->createTables();
    }

    public function testFillPost()
    {
        $post = SluggablePost::create(['title' => 'Hello World!']);
        $this->assertEquals('hello-world', $post->slug);
    }

    public function testSetAttributeOnPost()
    {
        $post = new SluggablePost;
        $post->title = "Let's go, rock show!";
        $post->save();

        $this->assertEquals('lets-go-rock-show', $post->slug);
    }

    public function testSetSlugAttributeManually()
    {
        $post = new SluggablePost;
        $post->title = 'We parked in a comfortable spot';
        $post->slug = 'war-is-pain';
        $post->save();

        $this->assertEquals('war-is-pain', $post->slug);
    }

    public function testConcatenatedSlug()
    {
        $post = new SluggablePost;
        $post->title = 'Sweetness and Light';
        $post->description = 'Itchee and Scratchee';
        $post->save();

        $this->assertEquals('sweetness-and-light-itchee-and-scratchee', $post->long_slug);
    }

    public function testDuplicateSlug()
    {
        $post1 = SluggablePost::create(['title' => 'Pace yourself']);
        $post2 = SluggablePost::create(['title' => 'Pace yourself']);
        $post3 = SluggablePost::create(['title' => 'Pace yourself']);

        $this->assertEquals('pace-yourself', $post1->slug);
        $this->assertEquals('pace-yourself-2', $post2->slug);
        $this->assertEquals('pace-yourself-3', $post3->slug);
    }

    public function testCollisionWithSelf()
    {
        $post1 = SluggablePost::create(['title' => 'Watch yourself']);
        $post2 = SluggablePost::create(['title' => 'Watch yourself']);
        $post3 = SluggablePost::create(['title' => 'Watch yourself']);

        $this->assertEquals('watch-yourself', $post1->slug);
        $this->assertEquals('watch-yourself-2', $post2->slug);
        $this->assertEquals('watch-yourself-3', $post3->slug);

        $post3->slugAttributes();
        $post3->save();
        $post2->slugAttributes();
        $post2->save();
        $post1->slugAttributes();
        $post1->save();

        $this->assertEquals('watch-yourself', $post1->slug);
        $this->assertEquals('watch-yourself-2', $post2->slug);
        $this->assertEquals('watch-yourself-3', $post3->slug);
    }

    public function testSuffixCollision()
    {
        $post1 = SluggablePost::create(['title' => 'Type 1']);
        $post2 = SluggablePost::create(['title' => 'Type 2']);
        $post3 = SluggablePost::create(['title' => 'Type 3']);
        $post4 = SluggablePost::create(['title' => 'Type 3']);
        $post5 = SluggablePost::create(['title' => 'Type 3']);

        $this->assertEquals('type-1', $post1->slug);
        $this->assertEquals('type-2', $post2->slug);
        $this->assertEquals('type-3', $post3->slug);
        $this->assertEquals('type-3-2', $post4->slug);
        $this->assertEquals('type-3-3', $post5->slug);
    }

    public function testSlugGenerationWithSoftDeletion()
    {
        /*
        * Slug Generation when identical key is softDeleted
        */
        $testSoftModelAllow1 = TestModelSluggableSoftDeleteAllow::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelAllow1->slug, 'test');

        $testSoftModelAllow1->delete();
        $this->assertNotNull($testSoftModelAllow1->deleted_at);

        $testSoftModelAllow2 = TestModelSluggableSoftDeleteAllow::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelAllow2->slug, 'test-2');

        /*
         * Fails with unique constraint and allowTrashedSlugs to false (default)
         */
        $testSoftModel1 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        $this->assertEquals($testSoftModel1->slug, 'test');

        $testSoftModel1->delete();
        $this->assertNotNull($testSoftModel1->deleted_at);

        $ok = true;

        try {
            $testSoftModel2 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        } catch (\Exception $e) {
            $ok = false;
        }
        $this->assertFalse($ok, 'Test should have failed');

        /**
        * Should ignore deleted slugs without error with no unique constraint
        */
        $testSoftModelNoUnique1 = TestModelSluggableSoftDeleteNoUnique::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelNoUnique1->slug, 'test');

        $testSoftModelNoUnique1->delete();
        $this->assertNotNull($testSoftModelNoUnique1->deleted_at);

        $testSoftModelNoUnique2 = TestModelSluggableSoftDeleteNoUnique::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelNoUnique2->slug, 'test');
    }

    public function testSlugGenerationWithHardDelete()
    {
        /*
        * Slug Generation when identical key was hardDeleted
        */
        $testSoftModel1 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        $this->assertEquals($testSoftModel1->slug, 'test');

        $testSoftModel1->forceDelete();

        $testSoftModel2 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        $this->assertEquals($testSoftModel2->slug, 'test');

        $testSoftModelAllow1 = TestModelSluggableSoftDeleteAllow::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelAllow1->slug, 'test');

        $testSoftModelAllow1->forceDelete();

        $testSoftModelAllow2 = TestModelSluggableSoftDeleteAllow::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelAllow2->slug, 'test');

        $testSoftModelNoUnique1 = TestModelSluggableSoftDeleteNoUnique::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelNoUnique1->slug, 'test');

        $testSoftModelNoUnique1->forceDelete();

        $testSoftModelNoUnique2 = TestModelSluggableSoftDeleteNoUnique::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelNoUnique2->slug, 'test');

        $testModel1 = TestModelSluggable::Create(['name' => 'test']);
        $this->assertEquals($testModel1->slug, 'test');

        $testModel1->delete();

        $testModel2 = TestModelSluggable::Create(['name' => 'test']);
        $this->assertEquals($testModel2->slug, 'test');
    }

    protected function createTables()
    {
        $this->getBuilder()->create('testSoftDelete', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->softDeletes();
            $table->timestamps();
        });

        $this->getBuilder()->create('testSoftDeleteNoUnique', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug');
            $table->softDeletes();
            $table->timestamps();
        });

        $this->getBuilder()->create('testSoftDeleteAllow', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->softDeletes();
            $table->timestamps();
        });

        $this->getBuilder()->create('test', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }
}

/*
* Class with Sluggable and SoftDelete traits
* with allowTrashedSlugs
*/
class TestModelSluggableSoftDeleteAllow extends \Winter\Storm\Database\Model
{
    use \Winter\Storm\Database\Traits\SoftDelete;
    use \Winter\Storm\Database\Traits\Sluggable;

    protected $slugs = ['slug' => 'name'];
    protected $fillable = ['name'];
    protected $table = 'testSoftDeleteAllow';
    protected $allowTrashedSlugs = true;
}

/*
* Class with Sluggable and SoftDelete traits
* with default behavior (allowTrashedSlugs = false)
*/
class TestModelSluggableSoftDelete extends \Winter\Storm\Database\Model
{
    use \Winter\Storm\Database\Traits\SoftDelete;
    use \Winter\Storm\Database\Traits\Sluggable;

    protected $slugs = ['slug' => 'name'];
    protected $fillable = ['name'];
    protected $table = 'testSoftDelete';
}

/*
* Class with Sluggable and SoftDelete traits
* with default behavior (allowTrashedSlugs = false)
*/
class TestModelSluggableSoftDeleteNoUnique extends \Winter\Storm\Database\Model
{
    use \Winter\Storm\Database\Traits\SoftDelete;
    use \Winter\Storm\Database\Traits\Sluggable;

    protected $slugs = ['slug' => 'name'];
    protected $fillable = ['name'];
    protected $table = 'testSoftDeleteNoUnique';
}

/*
* Class with only Sluggable trait
*/
class TestModelSluggable extends \Winter\Storm\Database\Model
{
    use \Winter\Storm\Database\Traits\Sluggable;

    protected $slugs = ['slug' => 'name'];
    protected $fillable = ['name'];
    protected $table = 'test';
}
