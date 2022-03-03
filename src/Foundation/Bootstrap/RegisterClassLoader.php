<?php namespace Winter\Storm\Foundation\Bootstrap;

use Winter\Storm\Support\ClassLoader;
use Winter\Storm\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;

class RegisterClassLoader
{
    /**
     * Register The Winter Auto Loader
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $loader = new ClassLoader(
            new Filesystem,
            $app->basePath(),
            $app->getCachedClassesPath()
        );

        $app->instance(ClassLoader::class, $loader);

        $loader->register();

        $app->after(function () use ($loader) {
            $loader->build();
        });
    }
}
