<?php

namespace Winter\Storm\Auth\Models;

use Winter\Storm\Database\Model;

/**
 * Password Reset model
 *
 * Represents a single password reset request.
 *
 * @author Winter CMS
 * @method \Winter\Storm\Database\Relations\BelongsToMany users() Users relation.
 */
class PasswordReset extends Model
{
    /**
     * @var string The table associated with the model.
     */
    protected $table = 'password_resets';

    /**
     * @var string[]|bool The attributes that aren't mass assignable.
     */
    protected $guarded = ['*'];
}
