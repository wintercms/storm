<?php

namespace Winter\Storm\Tests\Database\Fixtures\Models;

use Winter\Storm\Database\Model;

class Country extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_countries';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    public $hasMany = [
        'users' => [
            'Winter\Storm\Tests\Database\Fixtures\Models\User',
        ],
    ];

    public $hasManyThrough = [
        'posts' => [
            'Winter\Storm\Tests\Database\Fixtures\Models\Post',
            'through' => 'Winter\Storm\Tests\Database\Fixtures\Models\Author',
        ]
    ];
}
