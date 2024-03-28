<?php

namespace Winter\Storm\Tests\Database\Fixtures;

class ValidateablePost extends Post
{
    use \Winter\Storm\Database\Traits\Validation;

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    public $rules = [
        'title' => 'required|min:3|max:255',
        'slug' => ['required', 'regex:/^[a-z0-9\/\:_\-\*\[\]\+\?\|]*$/i', 'unique:database_tester_posts'],
    ];
}
