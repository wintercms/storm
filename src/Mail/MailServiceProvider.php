<?php namespace Winter\Storm\Mail;

use Illuminate\Mail\MailServiceProvider as MailServiceProviderBase;

class MailServiceProvider extends MailServiceProviderBase
{
    /**
     * Replace the Illuminate mailer instance with the Winter Mailer.
     *
     * @return void
     */
    protected function registerIlluminateMailer()
    {
        $this->app->singleton('mail.manager', function ($app) {
            return new MailManager($app);
        });

        $this->app->bind('mailer', function ($app) {
            return $app->make('mail.manager')->mailer();
        });
    }
}
