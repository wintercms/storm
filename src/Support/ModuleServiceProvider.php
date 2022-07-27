<?php namespace Winter\Storm\Support;

use Winter\Storm\Support\Str;
use Winter\Storm\Support\ClassLoader;
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
        $module = strtolower($this->getModule());
        $modulePath = base_path("modules/$module");

        // Register paths for: config, translator, view
        $this->loadViewsFrom($modulePath . '/views', $module);
        $this->loadTranslationsFrom($modulePath . '/lang', $module);
        $this->loadConfigFrom($modulePath . '/config', $module);

        // Register routes if present
        $routesFile = "$modulePath/routes.php";
        if (File::isFile($routesFile)) {
            $this->loadRoutesFrom($routesFile);
        }
    }

    /**
     * Registers the Module service provider.
     * @return void
     */
    public function register()
    {
        // Register this module with the application's ClassLoader for autoloading
        $module = $this->getModule();
        $this->app->make(ClassLoader::class)->autoloadPackage($module . '\\', "modules/" . strtolower($module) . '/');
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return [];
    }

    /**
     * Gets the name of this module
     */
    public function getModule(): string
    {
        return Str::before(get_class($this), '\\');
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
