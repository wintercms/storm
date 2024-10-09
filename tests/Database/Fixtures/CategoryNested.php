<?php

namespace Winter\Storm\Tests\Database\Fixtures;

use Illuminate\Database\Schema\Builder;

class CategoryNested extends Category
{
    use \Winter\Storm\Database\Traits\NestedTree;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_categories_nested';

    public static function migrateUp(Builder $builder): void
    {
        if ($builder->hasTable('database_tester_categories_nested')) {
            return;
        }

        $builder->create('database_tester_categories_nested', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('parent_id')->nullable();
            $table->integer('nest_left')->nullable();
            $table->integer('nest_right')->nullable();
            $table->integer('nest_depth')->nullable();
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
        if (!$builder->hasTable('database_tester_categories_nested')) {
            return;
        }

        $builder->dropIfExists('database_tester_categories_nested');
    }
}
