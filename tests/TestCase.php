<?php

use Orchestra\Testbench\Foundation\PackageManifest;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use PHPUnit\Framework\Assert;
use Winter\Storm\Foundation\Application;

class TestCase extends TestbenchTestCase
{
    /**
     * Resolve application implementation.
     *
     * @return \Winter\Storm\Foundation\Application
     */
    protected function resolveApplication()
    {
        return tap(new Application($this->getBasePath()), function ($app) {
            $app->bind(
                'Winter\Storm\Foundation\Bootstrap\LoadConfiguration',
                'Orchestra\Testbench\Bootstrap\LoadConfiguration'
            );

            PackageManifest::swap($app, $this);
        });
    }

    protected static function callProtectedMethod($object, $name, $params = [])
    {
        $className = get_class($object);
        $class = new ReflectionClass($className);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $params);
    }

    /**
     * Stub for `assertFileNotExists` to allow compatibility with both PHPUnit 8 and 9.
     *
     * @param string $filename
     * @param string $message
     * @return void
     */
    public static function assertFileNotExists(string $filename, string $message = ''): void
    {
        if (method_exists(Assert::class, 'assertFileDoesNotExist')) {
            Assert::assertFileDoesNotExist($filename, $message);
            return;
        }

        Assert::assertFileNotExists($filename, $message);
    }

    /**
     * Resolve application Console Kernel implementation.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function resolveApplicationConsoleKernel($app)
    {
        $app->singleton('Illuminate\Contracts\Console\Kernel', 'Winter\Storm\Foundation\Console\Kernel');
    }

    /**
     * Resolve application HTTP Kernel implementation.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function resolveApplicationHttpKernel($app)
    {
        $app->singleton('Illuminate\Contracts\Http\Kernel', 'Winter\Storm\Foundation\Http\Kernel');
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            /*
            * Laravel providers
            */
            Illuminate\Broadcasting\BroadcastServiceProvider::class,
            Illuminate\Bus\BusServiceProvider::class,
            Illuminate\Cache\CacheServiceProvider::class,
            Illuminate\Cookie\CookieServiceProvider::class,
            Illuminate\Encryption\EncryptionServiceProvider::class,
            Illuminate\Foundation\Providers\FoundationServiceProvider::class,
            Illuminate\Hashing\HashServiceProvider::class,
            Illuminate\Pagination\PaginationServiceProvider::class,
            Illuminate\Pipeline\PipelineServiceProvider::class,
            Illuminate\Queue\QueueServiceProvider::class,
            Illuminate\Session\SessionServiceProvider::class,
            Illuminate\View\ViewServiceProvider::class,
            Laravel\Tinker\TinkerServiceProvider::class,

            /*
            * Winter Storm providers
            */
            Winter\Storm\Foundation\Providers\ConsoleSupportServiceProvider::class,
            Winter\Storm\Database\DatabaseServiceProvider::class,
            Winter\Storm\Halcyon\HalcyonServiceProvider::class,
            Winter\Storm\Filesystem\FilesystemServiceProvider::class,
            Winter\Storm\Parse\ParseServiceProvider::class,
            Winter\Storm\Html\HtmlServiceProvider::class,
            Winter\Storm\Html\UrlServiceProvider::class,
            Winter\Storm\Network\NetworkServiceProvider::class,
            Winter\Storm\Flash\FlashServiceProvider::class,
            Winter\Storm\Mail\MailServiceProvider::class,
            Winter\Storm\Argon\ArgonServiceProvider::class,
            Winter\Storm\Redis\RedisServiceProvider::class,
            Winter\Storm\Validation\ValidationServiceProvider::class,
        ];
    }
}
