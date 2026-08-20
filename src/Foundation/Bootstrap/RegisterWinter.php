<?php namespace Winter\Storm\Foundation\Bootstrap;

use Winter\Storm\Foundation\Application;

class RegisterWinter
{
    /**
     * Specific features for Winter.
     *
     * @param \Winter\Storm\Foundation\Application $app
     * @return void
     */
    public function bootstrap(Application $app): void
    {
        /*
         * Workaround for CLI and URL based in subdirectory.
         *
         * The request must already be bound: resolving the URL generator without one throws,
         * because Illuminate\Routing\UrlGenerator requires a request instance. The console
         * kernel guarantees this by running SetRequestForConsole before this bootstrapper, and
         * HTTP requests never reach this branch because they are not running in console.
         *
         * An application server that boots from the CLI and then serves HTTP requests, such as
         * Laravel Octane, satisfies runningInConsole() while bootstrapping through the HTTP
         * kernel, which has no SetRequestForConsole. Skipping the workaround there is also
         * correct on its own terms: each served request supplies its own root URL, so forcing
         * the configured one would pin every generated URL to app.url for the worker's life.
         */
        if ($app->runningInConsole() && $app->bound('request')) {
            $app['url']->forceRootUrl($app['config']->get('app.url'));
        }

        /*
         * Register singletons
         */
        $app->singleton('string', function () {
            return new \Winter\Storm\Support\Str;
        });
        $app->singleton('svg', function () {
            return new \Winter\Storm\Support\Svg;
        });

        /*
         * Change paths based on config
         */
        if ($pluginsPath = $app['config']->get('cms.pluginsPathLocal')) {
            $app->setPluginsPath($pluginsPath);
        }

        if ($themesPath = $app['config']->get('cms.themesPathLocal')) {
            $app->setThemesPath($themesPath);
        }

        if ($tempPath = $app['config']->get('app.tempPath')) {
            $app->setTempPath($tempPath);
        }
    }
}
