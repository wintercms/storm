<?php

namespace Winter\Storm\Tests\Database\Traits;

class SoftDeleteTest extends \DbTestCase
{
    protected $seeded = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->seeded = [
            'posts' => [],
            'categories' => []
        ];

        $this->createTables();
        $this->seedTables();
    }

    protected function createTables()
    {
        $this->getBuilder()->create('posts', function ($table) {
            $table->increments('id');
            $table->string('title')->default('');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });

        $this->getBuilder()->create('categories', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->getBuilder()->create('categories_posts', function ($table) {
            $table->primary(['post_id', 'category_id']);
            $table->unsignedInteger('post_id');
            $table->unsignedInteger('category_id');
            $table->timestamp('deleted_at')->nullable();
        });
    }

    protected function seedTables()
    {
        $this->seeded['posts'][] = Post::create([
            'title' => 'First Post',
        ]);
        $this->seeded['posts'][] = Post::create([
            'title' => 'Second Post',
        ]);

        $this->seeded['categories'][] = Category::create([
            'name' => 'Category 1'
        ]);
        $this->seeded['categories'][] = Category::create([
            'name' => 'Category 2'
        ]);

        $this->seeded['posts'][0]->categories()->attach($this->seeded['categories'][0]);
        $this->seeded['posts'][0]->categories()->attach($this->seeded['categories'][1]);

        $this->seeded['posts'][1]->categories()->attach($this->seeded['categories'][0]);
        $this->seeded['posts'][1]->categories()->attach($this->seeded['categories'][1]);
    }

    public function testDeleteAndRestore()
    {
        $post = Post::first();
        $this->assertTrue($post->deleted_at === null);
        $this->assertTrue($post->categories()->where('deleted_at', null)->count() === 2);

        $post->delete();

        $post = Post::withTrashed()->first();
        $this->assertTrue($post->deleted_at != null);
        $this->assertTrue($post->categories()->where('deleted_at', '!=', null)->count() === 2);
        $post->restore();

        $post = Post::first();
        $this->assertTrue($post->deleted_at === null);
        $this->assertTrue($post->categories()->where('deleted_at', null)->count() === 2);
    }
}

class Post extends \Winter\Storm\Database\Model
{
    use \Winter\Storm\Database\Traits\SoftDelete;

    public $table = 'posts';

    public $fillable = ['title'];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public $belongsToMany = [
        'categories' => [
            Category::class,
            'table'      => 'categories_posts',
            'key'        => 'post_id',
            'otherKey'   => 'category_id',
            'softDelete' => true,
        ],
    ];
}

class Category extends \Winter\Storm\Database\Model
{
    public $table = 'categories';

    public $fillable = ['name'];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public $belongsToMany = [
        'posts' => [
            Post::class,
            'table'     => 'categories_posts',
            'key'       => 'category_id',
            'otherKey'  => 'post_id',
        ],
    ];
}
