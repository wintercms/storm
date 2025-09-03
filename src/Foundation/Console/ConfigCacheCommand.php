<?php

namespace Winter\Storm\Foundation\Console;

use Exception;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Foundation\Console\ConfigCacheCommand as BaseCommand;
use LogicException;
use Throwable;

class ConfigCacheCommand extends BaseCommand
{
    /**
     * @var string The console command signature.
     */
    protected $signature = 'config:cache
        {env? : Which environment should be cached?}
    ';

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function handle()
    {
        $args = [];
        if ($this->argument('env')) {
            $args['env'] = $this->argument('env');
        }

        // This is the only change to the parent, it allows us to only clear the requested config
        $this->callSilent('config:clear', $args);

        $config = $this->getFreshConfiguration();

        $configPath = $this->laravel->getCachedConfigPath();

        $this->files->put(
            $configPath, '<?php return '.var_export($config, true).';'.PHP_EOL
        );

        try {
            require $configPath;
        } catch (Throwable $e) {
            $this->files->delete($configPath);

            throw new LogicException('Your configuration files are not serializable.', 0, $e);
        }

        $this->components->info('Configuration cached successfully.');
    }

    /**
     * Boot a fresh copy of the application configuration.
     *
     * @return array
     */
    protected function getFreshConfiguration()
    {
        // This allows us to detect and override the "env by subdomain" feature of Winter
        if ($this->argument('env') && ($environment = $this->getEnvironmentConfiguration())) {
            // Grab hosts as env => domain
            $hosts = isset($environment['hosts'])
                ? array_flip($environment['hosts'])
                : [];

            // if we have env, set the host to the domain to "trick" the system into registering the correct config
            if (isset($hosts[$this->argument('env')])) {
                $_SERVER['HTTP_HOST'] = $hosts[$this->argument('env')];
            }
        }

        $app = require $this->laravel->bootstrapPath() . '/app.php';

        // This allows us to inform the LoadConfiguration class to not load from cache on fresh load
        $app['disableConfigCacheLoading'] = true;

        // This overrides the new app and existing app's env
        if ($this->argument('env')) {
            $this->laravel->detectEnvironment(fn() => $this->argument('env') ?? $app['env']);
            $app->detectEnvironment(fn() => $this->argument('env') ?? $app['env']);
        }

        // Stolen stuff from the Laravel command
        $app->useStoragePath($this->laravel->storagePath());
        $app->make(ConsoleKernelContract::class)->bootstrap();

        // Force preload all registered configs
        foreach ($app['config']->getNamespaces() as $namespace => $path) {
            foreach (glob($path . DIRECTORY_SEPARATOR . '*.php') as $file) {
                $app['config']->get($namespace . '::' . pathinfo($file, PATHINFO_FILENAME));
            }
        }

        return $app['config']->all();
    }

    /**
     * Load the environment configuration.
     * @TODO: This is copied from LoadConfiguration, should be exposed somewhere...
     * @see storm/src/Foundation/Bootstrap/LoadConfiguration.php
     */
    protected function getEnvironmentConfiguration(): array
    {
        $config = [];
        $environment = env('APP_ENV');
        if ($environment && file_exists($configPath = base_path() . '/config/' . $environment . '/environment.php')) {
            try {
                $config = require $configPath;
            }
            catch (Exception $ex) {
                //
            }
        }
        elseif (file_exists($configPath = base_path() . '/config/environment.php')) {
            try {
                $config = require $configPath;
            }
            catch (Exception $ex) {
                //
            }
        }

        return $config;
    }
}
