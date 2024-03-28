<?php

namespace Winter\Storm\Tests\Database\Fixtures;

use Illuminate\Database\Schema\Builder;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Relations\HasOne;

class UserLaravel extends Model
{
    use MigratesForTesting;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_users';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    public function author(): HasOne
    {
        return $this->hasOne(Author::class, 'user_id')->dependent();
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
