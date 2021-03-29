<?php namespace Winter\Storm\Parse;

use Illuminate\Support\ServiceProvider;

class ParseServiceProvider extends ServiceProvider
{
    /**
     * The container singletons that should be registered.
     *
     * @var array
     */
    public $singletons = [
        'parse.markdown' => Markdown::class,
        'parse.yaml' => Yaml::class,
        'parse.twig' => Twig::class,
        'parse.ini' => Ini::class,
    ];

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
