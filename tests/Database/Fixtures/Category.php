<?php

namespace Winter\Storm\Tests\Database\Fixtures;

use Illuminate\Database\Schema\Builder;
use Winter\Storm\Database\Model;

class Category extends Model
{
    use MigratesForTest;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_categories';

    public $belongsToMany = [
        'posts' => [
            Post::class,
            'table' => 'database_tester_categories_posts',
            'pivot' => ['category_name', 'post_name']
        ]
    ];

    public function getCustomNameAttribute()
    {
        return $this->name.' (#'.$this->id.')';
    }

    public static function migrateUp(Builder $builder): void
    {
        if ($builder->hasTable('database_tester_categories')) {
            return;
        }

        $builder->create('database_tester_categories', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('parent_id')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->nullable()->index()->unique();
            $table->string('description')->nullable();
            $table->integer('company_id')->unsigned()->nullable();
            $table->string('language', 3)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public static function migrateDown(Builder $builder): void
    {
        if (!$builder->hasTable('database_tester_categories')) {
            return;
        }

        $builder->drop('database_tester_categories');
    }
}
