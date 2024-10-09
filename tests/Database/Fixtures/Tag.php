<?php

namespace Winter\Storm\Tests\Database\Fixtures;

use Illuminate\Database\Schema\Builder;
use Winter\Storm\Database\Model;

class Tag extends Model
{
    use MigratesForTesting;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_tags';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    public $morphedByMany = [
        'authors' => [
            Author::class,
            'name'  => 'taggable',
            'table' => 'database_tester_taggables',
            'pivot' => ['added_by'],
        ],
        'posts'   => [
            Post::class,
            'name'  => 'taggable',
            'table' => 'database_tester_taggables',
            'pivot' => ['added_by'],
        ],
    ];

    public static function migrateUp(Builder $builder): void
    {
        if ($builder->hasTable('database_tester_tags')) {
            return;
        }

        $builder->create('database_tester_tags', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $builder->create('database_tester_taggables', function ($table) {
            $table->engine = 'InnoDB';
            $table->unsignedInteger('tag_id');
            $table->morphs('taggable', 'testings_taggable');
            $table->unsignedInteger('added_by')->nullable();
        });
    }

    public static function migrateDown(Builder $builder): void
    {
        if (!$builder->hasTable('database_tester_tags')) {
            return;
        }

        $builder->dropIfExists('database_tester_taggables');
        $builder->dropIfExists('database_tester_tags');
    }
}
