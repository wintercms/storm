<?php

namespace Winter\Storm\Auth\Passwords;

use Illuminate\Auth\Passwords\CanResetPassword as BaseCanResetPassword;
use Winter\Storm\Support\Facades\Config;
use Winter\Storm\Support\Facades\Mail;

trait CanResetPassword
{
    use BaseCanResetPassword;

    /**
     * {@inheritDoc}
     */
    public function sendPasswordResetNotification($token)
    {
        Mail::rawTo(
            $this->getEmailForPasswordReset(),
            $this->defaultPasswordResetEmail(),
            function ($message) {
                $message->subject('Password reset request');
            }
        );
    }

    /**
     * The default password reset email content.
     *
     * @return string
     */
    protected function defaultPasswordResetEmail()
    {
        $url = Config::get('app.url') . '/reset-password/' . $this->token;

        return 'Hi,' . "\n\n"
            . 'Someone has requested a password reset for your account. If this was you, please go to the following URL to reset your password.' . "\n\n"
            . $url . "\n\n"
            . 'If this was not you, please feel free to disregard this email.';
    }
}
