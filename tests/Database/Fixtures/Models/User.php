<?php

namespace Winter\Storm\Tests\Database\Fixtures\Models;

use Winter\Storm\Database\Model;

class User extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_users';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Relations
     */
    public $hasOne = [
        'author' => [
            'Winter\Storm\Tests\Database\Fixtures\Models\Author',
        ]
    ];

    public $hasOneThrough = [
        'phone' => [
            'Winter\Storm\Tests\Database\Fixtures\Models\Phone',
            'through' => 'Winter\Storm\Tests\Database\Fixtures\Models\Author',
        ],
    ];

    public $attachOne = [
        'avatar' => 'System\Models\File'
    ];

    public $attachMany = [
        'photos' => 'System\Models\File'
    ];
}
