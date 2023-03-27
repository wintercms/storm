<?php

namespace Winter\Storm\Tests\Database\Fixtures\Models;

use Winter\Storm\Database\Model;

class Post extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_posts';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'author' => 'Winter\Storm\Tests\Database\Fixtures\Models\Author',
    ];

    public $belongsToMany = [
        'categories' => [
            'Winter\Storm\Tests\Database\Fixtures\Models\Category',
            'table' => 'database_tester_categories_posts',
            'pivot' => ['category_name', 'post_name']
        ]
    ];

    public $morphMany = [
        'event_log' => ['Winter\Storm\Tests\Database\Fixtures\Models\EventLog', 'name' => 'related', 'delete' => true, 'softDelete' => true],
    ];

    public $morphOne = [
        'meta' => ['Winter\Storm\Tests\Database\Fixtures\Models\Meta', 'name' => 'taggable'],
    ];

    public $morphToMany = [
        'tags' => [
            'Winter\Storm\Tests\Database\Fixtures\Models\Tag',
            'name'  => 'taggable',
            'table' => 'database_tester_taggables',
            'pivot' => ['added_by']
        ],
    ];
}
