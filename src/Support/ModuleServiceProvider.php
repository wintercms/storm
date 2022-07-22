<?php namespace Winter\Storm\Support;

use Winter\Storm\Support\Facades\File;
use Illuminate\Support\ServiceProvider as ServiceProviderBase;

abstract class ModuleServiceProvider extends ServiceProviderBase
{
    /**
     * @var \Winter\Storm\Foundation\Application The application instance.
     */
    protected $app;
    
    /**
     * Bootstrap the application events.
     * @return void
     */
    public function boot()
    {
        if ($module = $this->getModule(func_get_args())) {
            /*
             * Register paths for: config, translator, view
             */
            $modulePath = base_path() . '/modules/' . $module;
            $this->loadViewsFrom($modulePath . '/views', $module);
            $this->loadTranslationsFrom($modulePath . '/lang', $module);
            $this->loadConfigFrom($modulePath . '/config', $module);

            /*
             * Add routes, if available
             */
            $routesFile = base_path() . '/modules/' . $module . '/routes.php';
            if (File::isFile($routesFile)) {
                $this->loadRoutesFrom($routesFile);
            }
        }
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return [];
    }

    public function getModule($args)
    {
        return (isset($args[0]) and is_string($args[0])) ? $args[0] : null;
    }

    /**
     * Registers a new console (artisan) command
     * @param string $key The command name
     * @param string $class The command class
     * @return void
     */
    public function registerConsoleCommand($key, $class)
    {
        $key = 'command.'.$key;

        $this->app->singleton($key, function ($app) use ($class) {
            return new $class;
        });

        $this->commands($key);
    }

    /**
     * Register a config file namespace.
     * @param  string  $path
     * @param  string  $namespace
     * @return void
     */
    protected function loadConfigFrom($path, $namespace)
    {
        $this->app['config']->package($namespace, $path);
    }
}
