<?php

namespace Winter\Storm\Tests\Database\Fixtures;

class NullablePost extends Post
{
    use \Winter\Storm\Database\Traits\Nullable;

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array List of attributes to nullify
     */
    protected $nullable = [
        'author_nickname',
    ];
}
