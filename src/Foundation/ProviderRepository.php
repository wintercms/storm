<?php namespace Winter\Storm\Foundation;

use Illuminate\Foundation\ProviderRepository as BaseProviderRepository;

class ProviderRepository extends BaseProviderRepository
{
    /**
     * Register the application service providers.
     *
     * This implementation differs from Laravel's base implementation in that deferred services are
     * recorded BEFORE service providers are registered, allowing eager loaded providers to use
     * deferred provider functionality. This has the consequence of making those deferred providers
     * eager loaded as well.
     *
     * @param  array  $providers
     * @return void
     */
    public function load(array $providers)
    {
        $manifest = $this->loadManifest();

        // First we will load the service manifest, which contains information on all
        // service providers registered with the application and which services it
        // provides. This is used to know which services are "deferred" loaders.
        if ($this->shouldRecompile($manifest, $providers)) {
            $manifest = $this->compileManifest($providers);
        }

        // Next, we will register events to load the providers for each of the events
        // that it has requested. This allows the service provider to defer itself
        // while still getting automatically loaded when a certain event occurs.
        foreach ($manifest['when'] as $provider => $events) {
            $this->registerLoadEvents($provider, $events);
        }

        // We will add the deferred services to the application so that they are able
        // to be resolved if necessary during the registration process of the eagerly
        // loaded providers.
        $this->app->addDeferredServices($manifest['deferred']);

        // We will go ahead and register all of the eagerly loaded providers with the
        // application so their services can be registered with the application as
        // a provided service.
        foreach ($manifest['eager'] as $provider) {
            $this->app->register($provider);
        }
    }
}
