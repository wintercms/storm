<?php namespace Winter\Storm\Foundation\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * {@inheritDoc}
     */
    protected $bootstrappers = [
        \Winter\Storm\Foundation\Bootstrap\RegisterClassLoader::class,
        \Winter\Storm\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Winter\Storm\Foundation\Bootstrap\LoadConfiguration::class,
        \Winter\Storm\Foundation\Bootstrap\LoadTranslation::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
        \Winter\Storm\Foundation\Bootstrap\RegisterWinter::class,
        \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
        \Illuminate\Foundation\Bootstrap\BootProviders::class,
    ];

    /**
     * {@inheritDoc}
     */
    protected $middleware = [
        \Winter\Storm\Foundation\Http\Middleware\CheckForTrustedHost::class,
        \Winter\Storm\Foundation\Http\Middleware\CheckForTrustedProxies::class,
        \Winter\Storm\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
    ];

    /**
     * {@inheritDoc}
     */
    protected $routeMiddleware = [
        // 'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        // 'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        // 'can' => \Illuminate\Auth\Middleware\Authorize::class,
        // 'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    ];

    /**
     * {@inheritDoc}
     */
    protected $middlewareGroups = [
        'web' => [
            \Winter\Storm\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            // \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
        'api' => [
            'throttle:60,1',
            'bindings',
        ],
    ];

    /**
     * {@inheritDoc}
     */
    protected $middlewarePriority = [
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        // \Illuminate\Auth\Middleware\Authenticate::class,
        // \Illuminate\Session\Middleware\AuthenticateSession::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        // \Illuminate\Auth\Middleware\Authorize::class,
    ];
}
