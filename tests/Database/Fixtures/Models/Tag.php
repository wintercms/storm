<?php

namespace Winter\Storm\Tests\Database\Fixtures\Models;

use Winter\Storm\Database\Model;

class Tag extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_tags';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    public $morphedByMany = [
        'authors' => [
            'Winter\Storm\Tests\Database\Fixtures\Models\Author',
            'name'  => 'taggable',
            'table' => 'database_tester_taggables',
            'pivot' => ['added_by'],
        ],
        'posts'   => [
            'Winter\Storm\Tests\Database\Fixtures\Models\Post',
            'name'  => 'taggable',
            'table' => 'database_tester_taggables',
            'pivot' => ['added_by'],
        ],
    ];
}
