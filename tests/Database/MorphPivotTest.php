<?php

namespace Winter\Storm\Tests\Database;

use Winter\Storm\Database\Model;
use Winter\Storm\Database\MorphPivot;
use Winter\Storm\Tests\Database\Fixtures\CustomMorphPivot;

class MorphPivotTest extends \DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->createTables();
    }

    public function testCreateMorphyToManyRelationAndCheckForMorphPivot()
    {
        // Create a couple of tags
        $cool = Tag::create([
            'name' => 'Cool',
        ]);
        $awesome = Tag::create([
            'name' => 'Awesome',
        ]);

        // Create a post
        $post = Post::create([
            'title' => 'Check this out',
            'body' => 'It is pretty cool and pretty awesome too',
        ]);

        // Attach tags
        $post->tags()->attach($cool);
        $post->tags()->attach($awesome);

        // Get first tag and get a pivot instance
        $pivot = $post->tags()->first()->pivot;

        $this->assertInstanceOf(MorphPivot::class, $pivot);
        $this->assertEquals('0', $pivot->hidden);
    }

    public function testCreateMorphyToManyRelationAndCheckForCustomMorphPivot()
    {
        // Create a couple of tags
        $cool = Tag::create([
            'name' => 'Cool',
        ]);
        $awesome = Tag::create([
            'name' => 'Awesome',
        ]);

        // Create a post
        $post = CustomPost::create([
            'title' => 'Check this out',
            'body' => 'It is pretty cool and pretty awesome too',
        ]);

        // Attach tags
        $post->tags()->attach($cool);
        $post->tags()->attach($awesome);

        // Get first tag and get a pivot instance
        $pivot = $post->tags()->first()->pivot;

        $this->assertInstanceOf(CustomMorphPivot::class, $pivot);
    }

    protected function createTables()
    {
        $this->getBuilder()->create('posts', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamps();
        });

        $this->getBuilder()->create('tags', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->getBuilder()->create('taggings', function ($table) {
            $table->increments('id');
            $table->integer('tag_id')->unsigned();
            $table->morphs('taggable');
            $table->boolean('hidden')->default(0);
            $table->timestamps();
        });
    }
}

class Post extends Model
{
    public $table = 'posts';

    public $fillable = [
        'title',
        'body',
    ];

    public $morphToMany = [
        'tags' => [
            Tag::class,
            'table' => 'taggings',
            'name' => 'taggable',
            'pivot' => ['hidden'],
        ],
    ];
}

class CustomPost extends Post
{
    public $morphToMany = [
        'tags' => [
            Tag::class,
            'table' => 'taggings',
            'name' => 'taggable',
            'pivot' => ['hidden'],
            'pivotModel' => CustomMorphPivot::class,
        ],
    ];
}

class Tagging extends Model
{
    public $table = 'taggings';

    protected $casts = [
        'hidden' => 'boolean',
    ];
}

class Tag extends Model
{
    public $table = 'tags';

    public $fillable = [
        'name',
    ];
}
