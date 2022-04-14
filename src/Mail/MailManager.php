<?php namespace Winter\Storm\Mail;

use InvalidArgumentException;
use Illuminate\Mail\MailManager as BaseMailManager;

/**
 * Overrides the Laravel MailManager
 * - Replaces the Laravel Mailer class with the Winter Mailer class
 * - Fires mailer.beforeRegister & mailer.register events
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
}
