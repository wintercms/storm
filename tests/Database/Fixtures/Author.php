<?php

namespace Winter\Storm\Tests\Database\Fixtures;

use Illuminate\Database\Schema\Builder;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Relations\BelongsToMany;
use Winter\Storm\Database\Relations\HasMany;
use Winter\Storm\Database\Relations\HasOne;

class Author extends Model
{
    use MigratesForTest;

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
        'user' => ['Database\Tester\Models\User', 'delete' => true],
        'country' => ['Database\Tester\Models\Country'],
        'user_soft' => ['Database\Tester\Models\SoftDeleteUser', 'key' => 'user_id', 'softDelete' => true],
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
        'meta' => ['Database\Tester\Models\Meta', 'name' => 'taggable'],
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

    public function scopes(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'database_tester_authors_roles');
    }

    public function executiveAuthors(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'database_tester_authors_roles')->wherePivot('is_executive', 1);
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
