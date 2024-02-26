<?php

namespace Winter\Storm\Tests\Database\Fixtures;

use Illuminate\Database\Schema\Builder;
use Winter\Storm\Database\Attributes\Relation;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Relations\HasMany;
use Winter\Storm\Database\Relations\HasOne;
use Winter\Storm\Database\Relations\MorphToMany;

class Author extends Model
{
    use MigratesForTesting;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'database_tester_authors';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'user' => [User::class, 'delete' => true],
        'country' => Country::class,
        'user_soft' => [SoftDeleteUser::class, 'key' => 'user_id', 'softDelete' => true],
    ];

    public $hasMany = [
        'posts' => Post::class,
    ];

    public $hasOne = [
        'phone' => Phone::class,
    ];

    public $belongsToMany = [
        'roles' => [
            'Winter\Storm\Tests\Database\Fixtures\Role',
            'table' => 'database_tester_authors_roles'
        ],
    ];

    public $morphMany = [
        'event_log' => [EventLog::class, 'name' => 'related', 'delete' => true, 'softDelete' => true],
    ];

    public $morphOne = [
        'meta' => [Meta::class, 'name' => 'taggable'],
    ];

    public $morphToMany = [
        'tags' => [
            Tag::class,
            'name'  => 'taggable',
            'table' => 'database_tester_taggables',
            'pivot' => ['added_by']
        ],
    ];

    public function contactNumber(): HasOne
    {
        return $this->hasOne(Phone::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    #[Relation]
    public function scopes()
    {
        return $this->belongsToMany(Role::class, 'database_tester_authors_roles');
    }

    #[Relation]
    public function executiveAuthors()
    {
        return $this->belongsToMany(Role::class, 'database_tester_authors_roles')->wherePivot('is_executive', 1);
    }

    #[Relation]
    public function info()
    {
        return $this->morphOne(Meta::class, 'taggable');
    }

    public function labels(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'database_tester_taggables')->withPivot('added_by');
    }

    #[Relation]
    public function auditLogs()
    {
        return $this->morphMany(EventLog::class, 'related')->dependent();
    }

    public static function migrateUp(Builder $builder): void
    {
        if ($builder->hasTable('database_tester_authors')) {
            return;
        }

        $builder->create('database_tester_authors', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->integer('country_id')->unsigned()->index()->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public static function migrateDown(Builder $builder): void
    {
        if (!$builder->hasTable('database_tester_authors')) {
            return;
        }

        $builder->dropIfExists('database_tester_authors');
    }
}
