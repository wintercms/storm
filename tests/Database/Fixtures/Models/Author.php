<?php

namespace Winter\Storm\Tests\Database\Fixtures\Models;

use Winter\Storm\Database\Model;

class Author extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_authors';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'user' => ['Winter\Storm\Tests\Database\Fixtures\Models\User', 'delete' => true],
        'country' => ['Winter\Storm\Tests\Database\Fixtures\Models\Country'],
        'user_soft' => ['Winter\Storm\Tests\Database\Fixtures\Models\SoftDeleteUser', 'key' => 'user_id', 'softDelete' => true],
    ];

    public $hasMany = [
        'posts' => 'Winter\Storm\Tests\Database\Fixtures\Models\Post',
    ];

    public $hasOne = [
        'phone' => 'Winter\Storm\Tests\Database\Fixtures\Models\Phone',
    ];

    public $belongsToMany = [
        'roles' => [
            'Winter\Storm\Tests\Database\Fixtures\Models\Role',
            'table' => 'database_tester_authors_roles'
        ],
        'executive_authors' => [
            'Winter\Storm\Tests\Database\Fixtures\Models\Role',
            'table' => 'database_tester_authors_roles',
            'conditions' => 'is_executive = 1'
        ],
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
