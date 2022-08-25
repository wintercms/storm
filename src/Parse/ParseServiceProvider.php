<?php namespace Winter\Storm\Parse;

use Illuminate\Support\ServiceProvider;
use Winter\Storm\Parse\Processor\Symfony3Processor;

class ParseServiceProvider extends ServiceProvider
{
    /**
     * The container singletons that should be registered.
     *
     * @var array
     */
    public $singletons = [
        'parse.markdown' => Markdown::class,
        'parse.twig' => Twig::class,
        'parse.ini' => Ini::class,
    ];

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->app->singleton('parse.yaml', function ($app) {
            $yaml = new Yaml();
            $yaml->setProcessor(new Symfony3Processor);

            return $yaml;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'parse.markdown',
            'parse.yaml',
            'parse.twig',
            'parse.ini',
        ];
    }
}
