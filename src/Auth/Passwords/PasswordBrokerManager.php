<?php

namespace Winter\Storm\Auth\Passwords;

use InvalidArgumentException;
use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Auth\Passwords\PasswordBrokerManager as BasePasswordBrokerManager;

class PasswordBrokerManager extends BasePasswordBrokerManager
{
    /**
     * {@inheritDoc}
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Password resetter [{$name}] is not defined.");
        }

        // The password broker uses a token repository to validate tokens and send user
        // password e-mails, as well as validating that password reset process as an
        // aggregate service of sorts providing a convenient interface for resets.
        return new PasswordBroker(
            $this->createTokenRepository($config),
            $this->getAuthInstance(),
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function createTokenRepository(array $config)
    {
        $key = $this->app['config']['app.key'];

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $passwordResetModel = $this->getAuthInstance()->createPasswordResetModel();

        if (isset($config['connection'])) {
            $connection = $config['connection'];
        } else {
            $connection = $passwordResetModel->getConnectionName();
        }

        return new DatabaseTokenRepository(
            $this->app['db']->connection($connection),
            $this->app['hash'],
            $config['table'] ?? $passwordResetModel->getTable(),
            $key,
            $config['expire'] ?? 180,
            $config['throttle'] ?? 30,
        );
    }

    /**
     * {@inheritDoc}
     *
     * @return array|null
     */
    protected function getConfig($name)
    {
        if ($name === 'winter') {
            return [];
        }

        return $this->app['config']["auth.passwords.{$name}"];
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['auth.defaults.passwords'] ?? 'winter';
    }

    /**
     * Returns the active auth instance.
     *
     * @return \Winter\Storm\Auth\Manager
     */
    protected function getAuthInstance()
    {
        return $this->app['auth'];
    }
}
