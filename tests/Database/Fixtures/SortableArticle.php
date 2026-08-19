<?php

namespace Winter\Storm\Tests\Database\Fixtures;

use Illuminate\Database\Schema\Builder;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Traits\HasSortableRelations;

class SortableArticle extends Model
{
    use MigratesForTesting;
    use HasSortableRelations;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_sortable_articles';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Sortable relations and their sort order pivot column.
     */
    public $sortableRelations = [
        'authors' => 'sort_order',
    ];

    /**
     * @var array Relations
     */
    public $belongsToMany = [
        'authors' => [
            Author::class,
            'table'    => 'database_tester_sortable_article_author',
            'key'      => 'article_id',
            'otherKey' => 'author_id',
        ],
    ];

    public static function migrateUp(Builder $builder): void
    {
        if ($builder->hasTable('database_tester_sortable_articles')) {
            return;
        }

        $builder->create('database_tester_sortable_articles', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('title')->nullable();
            $table->timestamps();
        });

        $builder->create('database_tester_sortable_article_author', function ($table) {
            $table->engine = 'InnoDB';
            $table->integer('article_id')->unsigned();
            $table->integer('author_id')->unsigned();
            $table->integer('sort_order')->default(0);
            $table->primary(['article_id', 'author_id']);
        });
    }

    public static function migrateDown(Builder $builder): void
    {
        if (!$builder->hasTable('database_tester_sortable_articles')) {
            return;
        }

        $builder->dropIfExists('database_tester_sortable_article_author');
        $builder->dropIfExists('database_tester_sortable_articles');
    }
}
