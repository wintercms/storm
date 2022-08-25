<?php namespace Winter\Storm\Auth\Models;

use Winter\Storm\Database\Model;

/**
 * Group model
 *
 * @method \Winter\Storm\Database\Relations\BelongsToMany users() Users relation.
 */
class Group extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    /**
     * @var string The table associated with the model.
     */
    protected $table = 'groups';

    /**
     * @var array Validation rules
     */
    public $rules = [
        'name' => 'required|between:4,16|unique:groups',
    ];

    /**
     * @var array Relations
     */
    public $belongsToMany = [
        'users' => [User::class, 'table' => 'users_groups']
    ];

    /**
     * @var string[]|bool The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Delete the group.
     * @return bool
     */
    public function delete()
    {
        $this->users()->detach();
        return parent::delete();
    }
}
