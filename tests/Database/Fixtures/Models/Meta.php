<?php

namespace Winter\Storm\Tests\Database\Fixtures\Models;

use Winter\Storm\Database\Model;

class Meta extends Model
{
    public $table = 'database_tester_meta';

    public $timestamps = false;

    public $morphTo = [
        'taggable' => []
    ];

    public $fillable = [
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'redirect_url',
        'robot_index',
        'robot_follow'
    ];
}
