<?php

namespace Winter\Storm\Tests\Database\Fixtures\Models;

use Winter\Storm\Database\Model;

/**
 * Role Model
 */
class Role extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_roles';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $belongsToMany = [
        'authors' => [
            'Winter\Storm\Tests\Database\Fixtures\Models\User',
            'table' => 'database_tester_authors_roles'
        ],
    ];
}
