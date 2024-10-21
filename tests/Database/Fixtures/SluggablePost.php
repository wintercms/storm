<?php

namespace Winter\Storm\Tests\Database\Fixtures;

class SluggablePost extends Post
{
    use \Winter\Storm\Database\Traits\Sluggable;

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array List of attributes to automatically generate unique URL names (slugs) for.
     */
    protected $slugs = [
        'slug' => 'title',
        'long_slug' => ['title', 'description']
    ];
}
