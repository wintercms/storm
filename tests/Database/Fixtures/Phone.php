<?php

namespace Winter\Storm\Tests\Database\Fixtures;

use Winter\Storm\Database\Model;
use Illuminate\Database\Schema\Builder;

class Phone extends Model
{
    use MigratesForTesting;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_phones';

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

    public static function migrateUp(Builder $builder): void
    {
        if ($builder->hasTable('database_tester_phones')) {
            return;
        }

        $builder->create('database_tester_phones', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('number')->nullable();
            $table->integer('author_id')->unsigned()->index()->nullable();
            $table->timestamps();
        });
    }

    public static function migrateDown(Builder $builder): void
    {
        if (!$builder->hasTable('database_tester_phones')) {
            return;
        }

        $builder->dropIfExists('database_tester_phones');
    }
}
