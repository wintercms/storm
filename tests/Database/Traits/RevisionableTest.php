<?php

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Winter\Storm\Database\MemoryCache;
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

    public function tearDown(): void
    {
        // Flush memory cache to prevent incorrect counts
        MemoryCache::instance()->flush();

        parent::tearDown();
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

    public $table = 'posts';
    protected $dates = ['deleted_at'];
    protected $fillable = ['name', 'content'];

    // Revision settings
    protected $revisionable = [
        'name',
        'content',
    ];
    protected $revisionableLimit = 8;
    public $morphMany = [
        'revision_history' => [Revision::class, 'name' => 'revisionable']
    ];
}
