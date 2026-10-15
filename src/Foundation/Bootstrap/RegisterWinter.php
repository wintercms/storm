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
         * Workaround for CLI and URL based in subdirectory. Skipped when no request is bound yet,
         * because the URL generator cannot be resolved without one (as when Laravel Octane boots
         * a worker through the HTTP kernel).
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
