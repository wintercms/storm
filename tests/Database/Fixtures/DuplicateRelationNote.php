<?php

namespace Winter\Storm\Tests\Database\Fixtures;

use Illuminate\Database\Schema\Builder;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Relations\BelongsTo;

class DuplicateRelationNote extends Model
{
    use MigratesForTesting;

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'author' => [
            Author::class,
        ]
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_notes';


    public static function migrateUp(Builder $builder): void
    {
        if ($builder->hasTable('database_tester_notes')) {
            return;
        }

        $builder->create('database_tester_notes', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('author_id')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public static function migrateDown(Builder $builder): void
    {
        if (!$builder->hasTable('database_tester_notes')) {
            return;
        }

        $builder->dropIfExists('database_tester_notes');
    }
}