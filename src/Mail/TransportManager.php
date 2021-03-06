<?php namespace Winter\Storm\Mail;

use Illuminate\Mail\TransportManager as BaseTransportManager;
use Winter\Storm\Mail\Transport\MandrillTransport;
use Winter\Storm\Mail\Transport\SparkPostTransport;

class TransportManager extends BaseTransportManager
{
    /**
     * Create an instance of the Mandrill Swift Transport driver.
     *
     * @return \Winter\Storm\Mail\Transport\MandrillTransport
     */
    protected function createMandrillDriver()
    {
        $config = $this->container['config']->get('services.mandrill', []);

        return new MandrillTransport(
            $this->guzzle($config),
            $config['secret']
        );
    }

    /**
     * Create an instance of the SparkPost Swift Transport driver.
     *
     * @return \Winter\Storm\Mail\Transport\SparkPostTransport
     */
    protected function createSparkPostDriver()
    {
        $config = $this->container['config']->get('services.sparkpost', []);

        return new SparkPostTransport(
            $this->guzzle($config),
            $config['secret'],
            $config['options'] ?? []
        );
    }
}
