<?php namespace Winter\Storm\Foundation\Bootstrap;

use Illuminate\Support\Env;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables as BaseLoadEnvironmentVariables;

class LoadEnvironmentVariables extends BaseLoadEnvironmentVariables
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        if ($app->configurationIsCached()) {
            return;
        }

        // Force Laravel to do the work
        parent::bootstrap($app);

        // Ensure that the application will always have an environment name set
        $app->detectEnvironment(function () {
            return Env::get('APP_ENV', 'production');
        });
    }
}
