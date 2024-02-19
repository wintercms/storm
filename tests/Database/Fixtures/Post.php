<?php

namespace Winter\Storm\Tests\Database\Fixtures;

use Illuminate\Database\Schema\Builder;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Relations\BelongsTo;

class Post extends Model
{
    use MigratesForTest;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_posts';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'author' => Author::class,
    ];

    public $belongsToMany = [
        'categories' => [
            'Database\Tester\Models\Category',
            'table' => 'database_tester_categories_posts',
            'pivot' => ['category_name', 'post_name']
        ]
    ];

    public $morphMany = [
        'event_log' => ['Database\Tester\Models\EventLog', 'name' => 'related', 'delete' => true, 'softDelete' => true],
    ];

    public $morphOne = [
        'meta' => ['Database\Tester\Models\Meta', 'name' => 'taggable'],
    ];

    public $morphToMany = [
        'tags' => [
            'Database\Tester\Models\Tag',
            'name'  => 'taggable',
            'table' => 'database_tester_taggables',
            'pivot' => ['added_by']
        ],
    ];

    /**
     * @return \Winter\Storm\Database\Relations\BelongsTo
     */
    public function writer(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    public static function migrateUp(Builder $builder): void
    {
        if ($builder->hasTable('database_tester_posts')) {
            return;
        }

        $builder->create('database_tester_posts', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('slug')->nullable()->index();
            $table->text('long_slug')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->integer('author_id')->unsigned()->index()->nullable();
            $table->string('author_nickname')->default('Winter')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public static function migrateDown(Builder $builder): void
    {
        if (!$builder->hasTable('database_tester_posts')) {
            return;
        }

        $builder->dropIfExists('database_tester_posts');
    }
}
