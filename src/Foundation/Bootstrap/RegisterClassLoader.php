<?php namespace Winter\Storm\Foundation\Bootstrap;

use Winter\Storm\Support\ClassLoader;
use Winter\Storm\Filesystem\Filesystem;
use Winter\Storm\Foundation\Application;

class RegisterClassLoader
{
    /**
     * Register the Winter class loader service.
     */
    public function bootstrap(Application $app): void
    {
        $loader = new ClassLoader(
            new Filesystem,
            $app->basePath(),
            $app->getCachedClassesPath()
        );

        $app->instance(ClassLoader::class, $loader);

        $loader->register();

        $loader->addDirectories([
            'modules',
            'plugins'
        ]);

        $app->after(function () use ($loader) {
            $loader->build();
        });
    }
}
