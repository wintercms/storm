<?php namespace Winter\Storm\Mail;

use InvalidArgumentException;
use Illuminate\Mail\MailManager as BaseMailManager;

/**
 * Overrides the Laravel MailManager
 * - Replaces the Laravel Mailer class with the Winter Mailer class
 * - Fires mailer.beforeRegister & mailer.register events
 * - Uses another method to determine old vs. new mail configs
 */
class MailManager extends BaseMailManager
{
    /*
     * Get a mailer instance by name.
     *
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Mail\Mailer
     */
    public function mailer($name = null)
    {
        /*
         * Extensibility
         */
        $this->app['events']->fire('mailer.beforeRegister', [$this]);

        return parent::mailer($name);
    }

    /**
     * Resolve the given mailer.
     *
     * @param  string  $name
     * @return \Winter\Storm\Mail\Mailer
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        /** @var array|null */
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Mailer [{$name}] is not defined.");
        }

        // Once we have created the mailer instance we will set a container instance
        // on the mailer. This allows us to resolve mailer classes via containers
        // for maximum testability on said classes instead of passing Closures.
        $mailer = new Mailer(
            $name,
            $this->app['view'],
            $this->createSymfonyTransport($config),
            $this->app['events']
        );

        if ($this->app->bound('queue')) {
            $mailer->setQueue($this->app['queue']);
        }

        // Next we will set all of the global addresses on this mailer, which allows
        // for easy unification of all "from" addresses as well as easy debugging
        // of sent messages since these will be sent to a single email address.
        foreach (['from', 'reply_to', 'to', 'return_path'] as $type) {
            $this->setGlobalAddress($mailer, $config, $type);
        }

        /*
         * Extensibility
         */
        $this->app['events']->fire('mailer.register', [$this, $mailer]);

        return $mailer;
    }

    /**
     * @inheritDoc
     */
    protected function getConfig(string $name)
    {
        // Here we will check if the "mailers" key exists and if it does, we will use that to
        // determine the applicable config. Laravel checks if the "drivers" key exists, and while
        // that does work for Laravel, it doesn't work in Winter when someone uses the Backend to
        // populate mail settings, as these mail settings are populated into the "mailers" key.
        return $this->app['config']['mail.mailers']
            ? ($this->app['config']["mail.mailers.{$name}"] ?? $this->app['config']['mail'])
            : $this->app['config']['mail'];
    }

    /**
     * @inheritDoc
     */
    public function getDefaultDriver()
    {
        // We will do the reverse of what Laravel does and check for "default" first, which is
        // populated by the Backend or the new "mail" config, before searching for the "driver"
        // key that was present in older version of Winter (<1.2).
        return $this->app['config']['mail.default'] ?? $this->app['config']['mail.driver'];
    }
}
