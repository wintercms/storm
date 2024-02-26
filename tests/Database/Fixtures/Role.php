<?php

namespace Winter\Storm\Tests\Database\Fixtures;

use Illuminate\Database\Schema\Builder;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Relations\BelongsToMany;

/**
 * Role Model
 */
class Role extends Model
{
    use MigratesForTesting;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_roles';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $belongsToMany = [
        'authors' => [
            User::class,
            'table' => 'database_tester_authors_roles'
        ],
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'database_tester_authors_roles');
    }

    public static function migrateUp(Builder $builder): void
    {
        if ($builder->hasTable('database_tester_roles')) {
            return;
        }

        $builder->create('database_tester_roles', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $builder->create('database_tester_authors_roles', function ($table) {
            $table->engine = 'InnoDB';
            $table->integer('author_id')->unsigned();
            $table->integer('role_id')->unsigned();
            $table->primary(['author_id', 'role_id']);
            $table->string('clearance_level')->nullable();
            $table->boolean('is_executive')->default(false);
        });
    }

    public static function migrateDown(Builder $builder): void
    {
        if (!$builder->hasTable('database_tester_roles')) {
            return;
        }

        $builder->dropIfExists('database_tester_roles');
        $builder->dropIfExists('database_tester_authors_roles');
    }
}
