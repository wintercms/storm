<?php namespace Winter\Storm\Support;

use Illuminate\Support\ServiceProvider as ServiceProviderBase;
use ReflectionClass;
use Winter\Storm\Foundation\Extension\WinterExtension;
use Winter\Storm\Support\ClassLoader;
use Winter\Storm\Support\Facades\File;
use Winter\Storm\Support\Str;
use Winter\Storm\Support\Traits\HasComposerPackage;

abstract class ModuleServiceProvider extends ServiceProviderBase implements WinterExtension
{
    use HasComposerPackage;

    /**
     * @var \Winter\Storm\Foundation\Application The application instance.
     */
    protected $app;

    protected string $path;

    protected string $identifier;

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

        // Bind the service provider to the application container
        $this->app->instance($this::class, $this);
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
        /** @var \Winter\Storm\Config\Repository */
        $config = $this->app['config'];
        $config->package($namespace, $path);
    }

    public function getVersion(): string
    {
        return $this->composerPackage['versions'][0] ?? 'dev-unknown';
    }

    public function getPath(): string
    {
        return $this->path ?? $this->path = dirname((new ReflectionClass(get_called_class()))->getFileName());
    }

    public function getIdentifier(): string
    {
        return $this->identifier ?? $this->identifier = (new ReflectionClass(get_called_class()))->getNamespaceName();
    }

    public function __toString(): string
    {
        return $this->getIdentifier();
    }
}
