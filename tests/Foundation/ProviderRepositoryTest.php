<?php

use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Foundation\ProviderRepository as LaravelProviderRepository;
use Winter\Storm\Config\ConfigServiceProvider;
use Winter\Storm\Filesystem\Filesystem;
use Winter\Storm\Foundation\Application;
use Winter\Storm\Foundation\ProviderRepository as WinterProviderRepository;
use Winter\Storm\Support\ServiceProvider;

class ProviderRepositoryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Mock application
        $this->testAppDir = dirname(__DIR__) . '/tmp/test-app';
        if (!is_dir($this->testAppDir . '/storage/framework')) {
            mkdir($this->testAppDir . '/storage/framework', 0777, true);
        }

        $this->basePath = $this->testAppDir;
        $this->app = new Application($this->basePath);
        $this->app->detectEnvironment(function () {
            return 'test';
        });
    }

    public function tearDown(): void
    {
        // Remove created files and folders
        if (is_file($this->testAppDir . '/storage/framework/packages.php')) {
            unlink($this->testAppDir . '/storage/framework/packages.php');
        }
        if (is_dir($this->testAppDir . '/storage/framework')) {
            rmdir($this->testAppDir . '/storage/framework');
        }
        if (is_dir($this->testAppDir . '/storage')) {
            rmdir($this->testAppDir . '/storage');
        }
        if (is_dir($this->testAppDir)) {
            rmdir($this->testAppDir);
        }

        parent::tearDown();
    }

    public function testOriginalFunctionaliy(): void
    {
        $this->expectException(\Illuminate\Contracts\Container\BindingResolutionException::class);
        $this->expectExceptionMessage('Target class [cache] does not exist.');

        $files = new Filesystem;

        // Simulate loading provider
        $repository = new LaravelProviderRepository($this->app, $files, $this->app->getCachedPackagesPath());
        $repository->load([
            ConfigServiceProvider::class,
            CacheServiceProvider::class,
            TestFixtureProvider::class,
        ]);

        $this->assertEquals('Tested!', $this->app['test']);
    }

    public function testWinterFunctionaliy(): void
    {
        $files = new Filesystem;

        // Simulate loading provider
        $repository = new WinterProviderRepository($this->app, $files, $this->app->getCachedPackagesPath());
        $repository->load([
            ConfigServiceProvider::class,
            CacheServiceProvider::class,
            TestFixtureProvider::class,
        ]);

        $this->assertEquals('Tested!', $this->app['test']);
    }
}

// Provider fixture for testing
class TestFixtureProvider extends ServiceProvider
{
    public function register()
    {
        // Test cache provider request - this should fail in the base functionality, but work in
        // Winter's implementation
        $thisValue = $this->app['cache']->get('some_value');

        $this->app->singleton('test', function () {
            return 'Tested!';
        });
    }
}
