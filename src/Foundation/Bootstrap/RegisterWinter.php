<?php namespace Winter\Storm\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;

class RegisterWinter
{
    /**
     * Specific features for WinterCMS.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        /*
         * Workaround for CLI and URL based in subdirectory
         */
        if ($app->runningInConsole()) {
            $app['url']->forceRootUrl($app['config']->get('app.url'));
        }

        /*
         * Register singletons
         */
        $app->singleton('string', function () {
            return new \Winter\Storm\Support\Str;
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
    }
}
