<?php namespace Winter\Storm\Filesystem;

use League\Flysystem\PathPrefixer;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemServiceProvider as FilesystemServiceProviderBase;

class FilesystemServiceProvider extends FilesystemServiceProviderBase
{
    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->extendFilesystemAdapter();

        $this->registerNativeFilesystem();

        $this->registerFlysystem();
    }

    /**
     * Extend Laravel's FilesystemAdapter class
     * @return void
     */
    protected function extendFilesystemAdapter()
    {
        FilesystemAdapter::macro('getPathPrefix', function () {
            /** @phpstan-ignore-next-line */
            return $this->prefixer->prefixPath('');
        });
        FilesystemAdapter::macro('setPathPrefix', function (string $prefix) {
            /** @phpstan-ignore-next-line */
            $this->prefixer = new PathPrefixer($prefix, $this->config['directory_separator'] ?? DIRECTORY_SEPARATOR);
        });
    }

    /**
     * Register the native filesystem implementation.
     * @return void
     */
    protected function registerNativeFilesystem()
    {
        $this->app->singleton('files', function () {
            $config = $this->app['config'];
            $files = new Filesystem;
            $files->filePermissions = $config->get('cms.defaultMask.file', null);
            $files->folderPermissions = $config->get('cms.defaultMask.folder', null);
            $files->pathSymbols = [
                '~' => base_path(),
                '$' => base_path() . $config->get('cms.pluginsDir', '/plugins'),
                '#' => base_path() . $config->get('cms.themesDir', '/themes'),
            ];
            return $files;
        });
    }

    /**
     * Register the filesystem manager.
     *
     * @return void
     */
    protected function registerManager()
    {
        $this->app->singleton('filesystem', function () {
            return new FilesystemManager($this->app);
        });
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return ['files', 'filesystem'];
    }
}
