<?php

namespace Winter\Storm\Auth\Passwords;

use Illuminate\Auth\Passwords\PasswordResetServiceProvider as BaseProvider;

/**
 * Provides base password reset functionality.
 */
class PasswordResetServiceProvider extends BaseProvider
{
    /**
     * Register the password broker instance.
     *
     * @return void
     */
    protected function registerPasswordBroker()
    {
        $this->app->singleton('auth.password', function ($app) {
            return new PasswordBrokerManager($app);
        });

        $this->app->bind('auth.password.broker', function ($app) {
            return $app->make('auth.password')->broker();
        });
    }
}
