<?php

namespace Winter\Storm\Tests\Database\Fixtures;

use Illuminate\Database\Schema\Builder;
use Winter\Storm\Database\Model;

class Meta extends Model
{
    use MigratesForTesting;

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

    public static function migrateUp(Builder $builder): void
    {
        if ($builder->hasTable('database_tester_meta')) {
            return;
        }

        $builder->create('database_tester_meta', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('taggable_id')->unsigned()->index()->nullable();
            $table->string('taggable_type')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('redirect_url')->nullable();
            $table->string('robot_index')->nullable();
            $table->string('robot_follow')->nullable();
        });
    }

    public static function migrateDown(Builder $builder): void
    {
        if (!$builder->hasTable('database_tester_meta')) {
            return;
        }

        $builder->dropIfExists('database_tester_meta');
    }
}
