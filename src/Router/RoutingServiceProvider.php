<?php namespace Winter\Storm\Router;

use Illuminate\Routing\RoutingServiceProvider as RoutingServiceProviderBase;

class RoutingServiceProvider extends RoutingServiceProviderBase
{
    /**
     * Boot the service provider.
     *
     * If routes are cached, ensure that the cached routes are loaded when the app is booted.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->routesAreCached()) {
            $this->app->booted(function () {
                require $this->app->getCachedRoutesPath();
            });
        }
    }

    /**
     * Register the router instance.
     *
     * @return void
     */
    protected function registerRouter()
    {
        $this->app->singleton('router', function ($app) {
            return new CoreRouter($app['events'], $app);
        });
    }
}
