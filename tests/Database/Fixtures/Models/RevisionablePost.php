<?php

namespace Winter\Storm\Tests\Database\Fixtures\Models;

class RevisionablePost extends Post
{
    use \Winter\Storm\Database\Traits\Revisionable;
    use \Winter\Storm\Database\Traits\SoftDelete;

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Dates
     */
    protected $dates = ['published_at', 'deleted_at'];

    /**
     * @var array Monitor these attributes for changes.
     */
    protected $revisionable = [
        'title',
        'slug',
        'description',
        'is_published',
        'published_at',
        'deleted_at'
    ];

    /**
     * @var int Maximum number of revision records to keep.
     */
    public $revisionableLimit = 8;

    /**
     * @var array Relations
     */
    public $morphMany = [
        'revision_history' => ['System\Models\Revision', 'name' => 'revisionable']
    ];

    /**
     * The user who made the revision.
     */
    public function getRevisionableUser()
    {
        return 7;
    }
}
