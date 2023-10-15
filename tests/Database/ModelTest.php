<?php

namespace Tests\Database;

use Illuminate\Support\Facades\DB;
use Winter\Storm\Database\Model;

class ModelTest extends \DbTestCase
{
    protected $seeded = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->createTables();
        $this->seedTables();
    }

    protected function createTables()
    {
        $this->getBuilder()->create('comments', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->nullableMorphs('commentable');
        });

        $this->getBuilder()->create('imageables', function ($table) {
            $table->foreignId('image_id')->nullable();
            $table->nullableMorphs('imageable');
        });

        $this->getBuilder()->create('images', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        $this->getBuilder()->create('phones', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->foreignId('user_id')->nullable();
        });

        $this->getBuilder()->create('posts', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->foreignId('user_id')->nullable();
        });

        $this->getBuilder()->create('role_user', function ($table) {
            $table->increments('id');
            $table->foreignId('role_id')->nullable();
            $table->foreignId('user_id')->nullable();
        });

        $this->getBuilder()->create('roles', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        $this->getBuilder()->create('tags', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->nullableMorphs('taggable');
        });

        $this->getBuilder()->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->foreignId('website_id')->nullable();
        });

        $this->getBuilder()->create('websites', function ($table) {
            $table->increments('id');
            $table->string('url');
        });

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

    protected function seedTables()
    {
        $this->seeded['comments'][] = Comment::create(['title' => 'Comment1']);
        $this->seeded['comments'][] = Comment::create(['title' => 'Comment2']);

        $this->seeded['images'][] = Image::create(['name' => 'Image1']);
        $this->seeded['images'][] = Image::create(['name' => 'Image2']);

        $this->seeded['phones'][] = Phone::create(['name' => 'Phone1']);
        $this->seeded['phones'][] = Phone::create(['name' => 'Phone2']);

        $this->seeded['roles'][] = Role::create(['name' => 'Role1']);
        $this->seeded['roles'][] = Role::create(['name' => 'Role2']);

        $this->seeded['tags'][] = Tag::create(['name' => 'Tag1']);
        $this->seeded['tags'][] = Tag::create(['name' => 'Tag2']);

        $this->seeded['posts'][] = Post::create(['title' => 'Post1']);
        $this->seeded['posts'][0]->comments()->add($this->seeded['comments'][0]);
        $this->seeded['posts'][0]->images()->attach($this->seeded['images'][0]);
        $this->seeded['posts'][0]->tag()->add($this->seeded['tags'][0]);

        $this->seeded['posts'][] = Post::create(['title' => 'Post2']);
        $this->seeded['posts'][1]->comments()->add($this->seeded['comments'][1]);
        $this->seeded['posts'][1]->images()->attach($this->seeded['images'][1]);
        $this->seeded['posts'][1]->tag()->add($this->seeded['tags'][1]);

        $this->seeded['users'][] = User::create(['name' => 'User1']);
        $this->seeded['users'][0]->phone()->add($this->seeded['phones'][0]);
        $this->seeded['users'][0]->posts()->add($this->seeded['posts'][0]);
        $this->seeded['users'][0]->posts()->add($this->seeded['posts'][1]);
        $this->seeded['users'][0]->roles()->attach($this->seeded['roles'][0]);

        $this->seeded['users'][] = User::create(['name' => 'User2']);
        $this->seeded['users'][1]->phone()->add($this->seeded['phones'][1]);
        $this->seeded['users'][1]->roles()->attach($this->seeded['roles'][0]);
        $this->seeded['users'][1]->roles()->attach($this->seeded['roles'][1]);

        $this->seeded['websites'][] = Website::create(['url' => 'https://wintercms.com']);
        $this->seeded['websites'][0]->users()->add($this->seeded['users'][0]);

        $this->seeded['websites'][] = Website::create(['url' => 'https://wintertricks.com']);
        $this->seeded['websites'][1]->users()->add($this->seeded['users'][1]);
    }

    // tests hasOneThrough & hasManyThrough
    public function testDeleteOnThroughRelations()
    {
        $website = $this->seeded['websites'][0];
        $user = $this->seeded['users'][0];

        $this->assertEquals(2, Phone::count());
        $this->assertEquals(2, Post::count());

        $this->assertEquals(1, $website->phone()->count());
        $this->assertEquals(2, $website->posts()->count());

        $this->assertEquals(1, $user->phone()->count());
        $this->assertEquals(2, $user->posts()->count());

        $website->delete();

        $this->assertEquals(1, $user->phone()->count());
        $this->assertEquals(2, $user->posts()->count());

        $this->assertEquals(2, Phone::count());
        $this->assertEquals(2, Post::count());
    }

    // tests hasMany
    public function testDeleteOnHasManyRelation()
    {
        $website = $this->seeded['websites'][0];
        $user = $this->seeded['users'][0];

        $this->assertEquals(2, Phone::count());
        $this->assertEquals(2, Post::count());

        $this->assertEquals(1, $website->phone()->count());
        $this->assertEquals(2, $website->posts()->count());

        $this->assertEquals(1, $user->phone()->count());
        $this->assertEquals(2, $user->posts()->count());

        $this->assertEquals(1, $website->users()->count());

        $website->delete();

        $this->assertEquals(1, Website::count());
        $this->assertEquals(2, User::count());

        // test with relation "delete" flag set to true
        Website::extend(function ($model) {
            $model->hasMany['users']['delete'] = true;
        });

        $website = Website::find($this->seeded['websites'][1]->id);

        $this->assertEquals(1, $website->users()->count());

        $website->delete();

        $this->assertEquals(0, Website::count());
        $this->assertEquals(1, User::count());
    }

    // tests morphMany
    public function testDeleteOnMorphManyRelation()
    {
        $post = $this->seeded['posts'][0];

        $this->assertEquals(2, Post::count());
        $this->assertEquals(2, Comment::count());

        $this->assertEquals(1, $post->comments()->count());

        $post->delete();

        $this->assertEquals(1, Post::count());
        $this->assertEquals(2, Comment::count());

        // test with relation "delete" flag set to true
        Post::extend(function ($model) {
            $model->morphMany['comments']['delete'] = true;
        });

        $post = Post::find($this->seeded['posts'][1]->id);

        $this->assertEquals(1, $post->comments()->count());

        $post->delete();

        $this->assertEquals(0, Post::count());
        $this->assertEquals(1, Comment::count());
    }

    // tests belongsToMany
    public function testDeleteOnBelongsToManyRelation()
    {
        $user = $this->seeded['users'][0];

        $this->assertEquals(2, User::count());
        $this->assertEquals(2, Role::count());

        $this->assertEquals(1, $user->roles()->count());

        $this->assertEquals(3, DB::table('role_user')->count());

        $user->delete();

        // verify that pivot record has been removed
        $this->assertEquals(2, DB::table('role_user')->count());

        // verify user has been deleted
        $this->assertEquals(1, User::count());

        // verify both roles still exist
        $this->assertEquals(2, Role::count());

        // test with relation "detach" flag set to false (default is true)
        User::extend(function ($model) {
            $model->belongsToMany['roles']['detach'] = false;
        });

        $user = User::find($this->seeded['users'][1]->id);

        $this->assertEquals(2, $user->roles()->count());
        $this->assertEquals(2, DB::table('role_user')->count());

        $user->delete();

        // verify pivot record has NOT been removed
        $this->assertEquals(2, DB::table('role_user')->count());

        // verify both roles still exist
        $this->assertEquals(2, Role::count());
    }

    // tests morphToMany
    public function testDeleteOnMorphToManyRelation()
    {
        $post = $this->seeded['posts'][0];

        $this->assertEquals(2, Post::count());
        $this->assertEquals(2, Image::count());

        $this->assertEquals(1, $post->images()->count());
        $this->assertEquals(1, $this->seeded['images'][0]->posts()->count());

        $this->assertEquals(2, DB::table('imageables')->count());

        $post->delete();

        // verify that pivot record has been removed
        $this->assertEquals(1, DB::table('imageables')->count());

        // verify post has been deleted
        $this->assertEquals(1, Post::count());

        // verify image still exists
        $this->assertEquals(2, Image::count());

        // test with relation "detach" flag set to false (default is true)
        Post::extend(function ($model) {
            $model->morphToMany['images']['detach'] = false;
        });

        $post = Post::find($this->seeded['posts'][1]->id);

        $this->assertEquals(1, $post->images()->count());

        $post->delete();

        // verify that pivot record has NOT been removed
        $this->assertEquals(1, DB::table('imageables')->count());

        $this->assertEquals(0, Post::count());
        $this->assertEquals(2, Image::count());
    }

    // tests morphedByMany
    public function testDeleteOnMorphedByManyRelation()
    {
        $image = $this->seeded['images'][0];

        $this->assertEquals(2, Image::count());
        $this->assertEquals(2, Post::count());

        $this->assertEquals(1, $image->posts()->count());

        $this->assertEquals(2, DB::table('imageables')->count());

        $image->delete();

        // verify that pivot record has been removed
        $this->assertEquals(1, DB::table('imageables')->count());

        // verify image has been deleted
        $this->assertEquals(1, Image::count());

        // verify post still exists
        $this->assertEquals(2, Post::count());

        // test with relation "detach" flag set to false (default is true)
        Image::extend(function ($model) {
            $model->morphedByMany['posts']['detach'] = false;
        });

        $image = Image::find($this->seeded['images'][1]->id);

        $this->assertEquals(1, $image->posts()->count());

        $image->delete();

        // verify that pivot record has NOT been removed
        $this->assertEquals(1, DB::table('imageables')->count());

        $this->assertEquals(0, Image::count());
        $this->assertEquals(2, Post::count());
    }

    // tests hasOne
    public function testDeleteOnHasOneRelation()
    {
        $user = $this->seeded['users'][0];

        $this->assertEquals(1, $user->phone()->count());
        $this->assertEquals(2, Phone::count());

        $user->delete();

        $this->assertEquals(2, Phone::count());

        // test with relation "delete" flag set to true
        User::extend(function ($model) {
            $model->hasOne['phone']['delete'] = true;
        });

        $user = User::find($this->seeded['users'][1]->id);

        $this->assertEquals(1, $user->phone()->count());
        $this->assertEquals(2, Phone::count());

        $user->delete();

        $this->assertEquals(1, Phone::count());
    }

    // tests morphOne
    public function testDeleteOnMorphOneRelation()
    {
        $post = $this->seeded['posts'][0];

        $this->assertEquals(1, $post->tag()->count());
        $this->assertEquals(2, Tag::count());

        $post->delete();

        $this->assertEquals(2, Tag::count());

        // test with relation "delete" flag set to true
        Post::extend(function ($model) {
            $model->morphOne['tag']['delete'] = true;
        });

        $post = Post::find($this->seeded['posts'][1]->id);

        $this->assertEquals(1, $post->tag()->count());
        $this->assertEquals(2, Tag::count());

        $post->delete();

        $this->assertEquals(1, Tag::count());
    }

    // tests belongsTo
    public function testDeleteOnBelongsToRelation()
    {
        $phone = $this->seeded['phones'][0];
        $this->assertEquals(2, User::count());
        $this->assertEquals(2, Phone::count());

        $phone->delete();

        // verify phone has been deleted
        $this->assertEquals(1, Phone::count());

        // verify user has NOT been deleted
        $this->assertEquals(2, User::count());
    }

    // tests morphTo
    public function testDeleteOnMorphToRelation()
    {
        $comment = $this->seeded['comments'][0];
        $this->assertEquals(2, Comment::count());
        $this->assertEquals(2, Post::count());

        $comment->delete();

        // verify comment has been deleted
        $this->assertEquals(1, Comment::count());

        // verify post has NOT been deleted
        $this->assertEquals(2, Post::count());
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
        $nameWithSpace = "  ${name}  ";
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

    public function testUpsert()
    {
        $this->getBuilder()->create('test_model2', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        $this->getBuilder()->create('test_model_middle', function ($table) {
            $table->increments('id');
            $table->string('value')->nullable();
            $table->timestamps();

            $table->integer('model1_id')->unsigned();
            $table->integer('model2_id')->unsigned();

            $table->foreign('model1_id')->references('id')->on('test_model1');
            $table->foreign('model2_id')->references('id')->on('test_model2');
            $table->unique(['model1_id', 'model2_id']);
        });

        $model1Row = TestModelGuarded::create([
            'name' => 'Row 1',
            'data' => 'Test data'
        ]);

        $model2Row = TestModel2::create([
            'name' => 'Test',
        ]);

        $test3Row = TestModelMiddle::create([
            'model1_id' => $model1Row->id,
            'model2_id' => $model2Row->id,
            'value' => '1'
        ]);

        TestModelMiddle::upsert([
            'model1_id' => $model1Row->id,
            'model2_id' => $model2Row->id,
            'value' => '1'
        ], ['model1_id', 'model2_id'], ['value']);

        $modelMiddleRow = TestModelMiddle::first();

        $this->assertEquals('1', $modelMiddleRow->value);

        TestModelMiddle::upsert([
            'model1_id' => $model1Row->id,
            'model2_id' => $model2Row->id,
            'value' => '2'
        ], ['model1_id', 'model2_id'], ['value']);

        $modelMiddleRow = TestModelMiddle::first();

        $this->assertEquals('2', $modelMiddleRow->value);
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

class TestModel2 extends Model
{
    protected $guarded = [];

    public $table = 'test_model2';
}

class TestModelMiddle extends Model
{
    protected $guarded = [];

    public $table = 'test_model_middle';
}

class BaseModel extends Model
{
    protected static $unguarded = true;
    public $timestamps = false;
}

class Comment extends BaseModel
{
    public $morphTo = [
        'commentable' => []
    ];
}

class Image extends BaseModel
{
    public $morphedByMany = [
        'posts' => [Post::class, 'name' => 'imageable'],
    ];
}

class Phone extends BaseModel
{
    public $belongsTo = [
        'user' => [User::class]
    ];
}

class Post extends BaseModel
{
    public $belongsTo = [
        'user' => [User::class]
    ];
    public $morphOne = [
        'tag' => [Tag::class, 'name' => 'taggable']
    ];
    public $morphMany = [
        'comments' => [Comment::class, 'name' => 'commentable']
    ];
    public $morphToMany = [
        'images' => [Image::class, 'name' => 'imageable']
    ];
}

class Role extends BaseModel
{
    public $belongsToMany = [
        'users' => [User::class]
    ];
}

class Tag extends BaseModel
{
    public $morphTo = [
        'taggable' => []
    ];
}

class User extends BaseModel
{
    public $hasOne = [
        'phone' => [Phone::class]
    ];
    public $hasMany = [
        'posts' => [Post::class]
    ];
    public $belongsTo = [
        'website' => [Website::class]
    ];
    public $belongsToMany = [
        'roles' => [Role::class]
    ];
}

class Website extends BaseModel
{
    public $hasMany = [
        'users' => [User::class]
    ];
    public $hasOneThrough = [
        'phone' => [Phone::class, 'through' => User::class]
    ];
    public $hasManyThrough = [
        'posts' => [Post::class, 'through' => User::class]
    ];
}
