<?php

namespace Winter\Storm\Tests\Database\Fixtures;

use Illuminate\Database\Schema\Builder;
use Winter\Storm\Database\Attach\File;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Relations\HasOneThrough;

class User extends Model
{
    use MigratesForTest;

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
            Author::class,
        ]
    ];

    public $hasOneThrough = [
        'phone' => [
            Phone::class,
            'through' => Author::class,
        ],
    ];

    public $attachOne = [
        'avatar' => File::class,
    ];

    public $attachMany = [
        'photos' => File::class,
    ];

    public function contactNumber(): HasOneThrough
    {
        return $this->hasOneThrough(Phone::class, Author::class);
    }

    public static function migrateUp(Builder $builder): void
    {
        if ($builder->hasTable('database_tester_users')) {
            return;
        }

        $builder->create('database_tester_users', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public static function migrateDown(Builder $builder): void
    {
        if (!$builder->hasTable('database_tester_users')) {
            return;
        }

        $builder->dropIfExists('database_tester_users');
    }
}
