<?php

namespace Winter\Storm\Tests\Database\Fixtures;

use Illuminate\Database\Schema\Builder;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Relations\HasManyThrough;

class Country extends Model
{
    use MigratesForTest;

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
            User::class,
        ],
    ];

    public $hasManyThrough = [
        'posts' => [
            Post::class,
            'through' => Author::class,
        ]
    ];

    public function messages(): HasManyThrough
    {
        return $this->hasManyThrough(Post::class, Author::class);
    }

    public static function migrateUp(Builder $builder): void
    {
        if ($builder->hasTable('database_tester_countries')) {
            return;
        }

        $builder->create('database_tester_countries', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public static function migrateDown(Builder $builder): void
    {
        if (!$builder->hasTable('database_tester_countries')) {
            return;
        }

        $builder->dropIfExists('database_tester_countries');
    }
}