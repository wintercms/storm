<?php

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Winter\Storm\Argon\Argon;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Models\Revision;

class RevisionableTest extends DbTestCase
{
    use ArraySubsetAsserts;

    public function setUp(): void
    {
        parent::setUp();

        $this->createTables();
    }

    public function testRevisions()
    {
        // Create initial post
        $post = RevisionablePost::create([
            'name' => 'This is a post',
            'content' => 'This is some content'
        ]);

        $this->assertEquals(0, $post->revision_history()->count());

        // Make an edit on a single field - there should be a single revision
        $post->name = 'This is a revised post';
        $post->save();

        $this->assertEquals(1, $post->revision_history()->count());

        // Check revision data is correct
        $revision = $post->revision_history()->first();

        $this->assertArraySubset([
            'field' => 'name',
            'old_value' => 'This is a post',
            'new_value' => 'This is a revised post',
            'revisionable_type' => RevisionablePost::class,
            'revisionable_id' => $post->id,
        ], $revision->attributes);

        // Make an edit on multiple fields - there should be multiple revisions
        $post->name = 'This is a newly revised post';
        $post->content = 'With some newly posted content';
        $post->save();

        $this->assertEquals(3, $post->revision_history()->count());

        // Check revision data is correct
        $revisions = $post->revision_history()
            ->orderBy('id', 'desc')
            ->limit(2)
            ->get()
            ->reverse()
            ->values()
            ->toArray();

        $this->assertArraySubset([
            [
                'field' => 'name',
                'old_value' => 'This is a revised post',
                'new_value' => 'This is a newly revised post',
                'revisionable_type' => RevisionablePost::class,
                'revisionable_id' => $post->id,
            ],
            [
                'field' => 'content',
                'old_value' => 'This is some content',
                'new_value' => 'With some newly posted content',
                'revisionable_type' => RevisionablePost::class,
                'revisionable_id' => $post->id,
            ]
        ], $revisions);
    }

    public function testRevisionsDisabled()
    {
        // Create initial post
        $post = RevisionablePost::create([
            'name' => 'This is a post',
            'content' => 'This is some content'
        ]);

        $this->assertEquals(0, $post->revision_history()->count());

        // Make an edit with revisions disabled
        $post->revisionsEnabled = false;
        $post->name = 'This is a revised post';
        $post->save();

        $this->assertEquals(0, $post->revision_history()->count());
    }

    public function testRevisionsLimit()
    {
        $post = RevisionablePost::create([
            'name' => 'This is a post',
            'content' => 'This is some content'
        ]);

        for ($i = 0; $i < 7; ++$i) {
            $post->name = 'Post edit #' . ($i + 1);
            $post->save();
        }

        $this->assertEquals(7, $post->revision_history()->count());

        // Do an eighth edit, this should have a revision created
        $post->name = 'Post edit #8';
        $post->save();

        $this->assertEquals(8, $post->revision_history()->count());

        // Do a ninth edit, this should have a revision created but the first edit should drop off
        $post->name = 'Post edit #9';
        $post->save();

        $this->assertEquals(8, $post->revision_history()->count());

        $this->assertEquals([
            'Post edit #2',
            'Post edit #3',
            'Post edit #4',
            'Post edit #5',
            'Post edit #6',
            'Post edit #7',
            'Post edit #8',
            'Post edit #9',
        ], $post->revision_history()->get()->pluck('new_value')->toArray());

        // Editing multiple fields counts as multiple revisions
        $post->name = 'Post edit #10';
        $post->content = 'Happy anniversary!';
        $post->save();

        $this->assertEquals(8, $post->revision_history()->count());

        $this->assertEquals([
            'Post edit #4',
            'Post edit #5',
            'Post edit #6',
            'Post edit #7',
            'Post edit #8',
            'Post edit #9',
            'Post edit #10',
            'Happy anniversary!'
        ], $post->revision_history()->get()->pluck('new_value')->toArray());
    }

    public function testRevisionsSoftDelete()
    {
        Argon::setTestNow();

        $post = RevisionablePost::create([
            'name' => 'This is a post',
            'content' => 'This is some content'
        ]);

        $post->delete();
        $this->assertEquals(1, $post->revision_history()->count());

        // Check revision data is correct
        $revision = $post->revision_history()->first();

        $this->assertArraySubset([
            'field' => 'deleted_at',
            'old_value' => null,
            'new_value' => Argon::now(),
            'revisionable_type' => RevisionablePost::class,
            'revisionable_id' => $post->id,
        ], $revision->attributes);
    }

    public function testRevisionsForceDelete()
    {
        Argon::setTestNow();

        $post = RevisionablePost::create([
            'name' => 'This is a post',
            'content' => 'This is some content'
        ]);

        $post->forceDelete();

        // The revision should still be available
        $this->assertEquals(1, $post->revision_history()->count());

        // Check revision data is correct
        $revision = $post->revision_history()->first();
        $this->assertArraySubset([
            'field' => 'deleted_at',
            'old_value' => null,
            'new_value' => Argon::now(),
            'revisionable_type' => RevisionablePost::class,
            'revisionable_id' => $post->id,
        ], $revision->attributes);
    }

    protected function createTables()
    {
        $this->runMigrations();

        $this->db->schema()->create('posts', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('content');
            $table->softDeletes();
            $table->timestamps();
        });
    }
}

class RevisionablePost extends Model
{
    use \Winter\Storm\Database\Traits\Revisionable;
    use \Winter\Storm\Database\Traits\SoftDelete;

    public $table = 'posts';
    protected $dates = ['deleted_at'];
    protected $fillable = ['name', 'content'];

    // Revision settings
    protected $revisionable = [
        'name',
        'content',
        'deleted_at'
    ];
    protected $revisionableLimit = 8;
    public $morphMany = [
        'revision_history' => [Revision::class, 'name' => 'revisionable']
    ];
}
