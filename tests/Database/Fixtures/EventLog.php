<?php

namespace Winter\Storm\Tests\Database\Fixtures;

use Illuminate\Database\Schema\Builder;
use Winter\Storm\Database\Model;

class EventLog extends Model
{
    use MigratesForTesting;
    use \Winter\Storm\Database\Traits\SoftDelete;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_event_log';

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
    public $morphTo = [
        'related' => []
    ];

    public static function migrateUp(Builder $builder): void
    {
        if ($builder->hasTable('database_tester_event_log')) {
            return;
        }

        $builder->create('database_tester_event_log', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('action', 30)->nullable();
            $table->string('related_id')->index()->nullable();
            $table->string('related_type')->index()->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public static function migrateDown(Builder $builder): void
    {
        if (!$builder->hasTable('database_tester_event_log')) {
            return;
        }

        $builder->dropIfExists('database_tester_event_log');
    }
}
