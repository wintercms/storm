<?php

use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Winter\Storm\Foundation\Application;
use Winter\Storm\Support\ServiceProvider;

class PackageCacheTest extends PHPUnitTestCase
{
    protected string $basePath;

    protected $facadeApp;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/winter-storm-' . uniqid();

        $files = new Filesystem;
        $files->makeDirectory($this->basePath . '/storage/framework', 0777, true);
        $files->makeDirectory($this->basePath . '/vendor/composer', 0777, true);
        $files->put($this->basePath . '/composer.json', '{"name": "winter/test-app"}');

        // A stale cached package manifest, naming a provider from a package that Composer has since removed
        $files->put($this->basePath . '/storage/framework/packages.php', '<?php return ' . var_export([
            'winter/removed-package' => ['providers' => ['Winter\\Removed\\ServiceProvider']],
        ], true) . ';');

        $this->facadeApp = Facade::getFacadeApplication();
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->facadeApp);

        (new Filesystem)->deleteDirectory($this->basePath);
    }

    /**
     * Provider loading can only retry successfully if the cleared manifest actually rebuilds, so guard that on its own as well.
     */
    public function testClearingThePackageCacheRebuildsTheManifest()
    {
        $this->discoverProviders([CachedPackageServiceProvider::class]);

        $app = $this->makeApplication();

        // Prime the manifest from the stale cache
        $this->assertSame(['Winter\\Removed\\ServiceProvider'], $app->make(PackageManifest::class)->providers());

        (new ReflectionMethod($app, 'clearPackageCache'))->invoke($app);

        $this->assertSame([CachedPackageServiceProvider::class], $app->make(PackageManifest::class)->providers());
    }

    /**
     * Loading the stale manifest fails, so the application clears the caches and tries once more. That retry has to rebuild the manifest from the installed packages, rather than carry on with no discovered packages at all.
     */
    public function testProviderLoadingRetryRebuildsTheClearedPackageManifest()
    {
        $this->discoverProviders([CachedPackageServiceProvider::class]);

        $app = $this->makeApplication();

        $app->registerConfiguredProviders();

        $this->assertNotNull($app->getProvider(CachedPackageServiceProvider::class));
        $this->assertTrue($app->bound('cached-package'));
    }

    /**
     * Discarding the discovered packages on the retry also hid genuinely broken providers, since the retry then had nothing left to fail on. A provider that is still missing after the rebuild has to reach the caller.
     */
    public function testProviderLoadingReportsAProviderThatIsStillMissingAfterTheRetry()
    {
        $this->discoverProviders(['Winter\\Missing\\ServiceProvider']);

        $app = $this->makeApplication();

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Class "Winter\Missing\ServiceProvider" not found');

        $app->registerConfiguredProviders();
    }

    /**
     * Declares the given providers as an installed package for Laravel's package discovery to find.
     */
    protected function discoverProviders(array $providers): void
    {
        (new Filesystem)->put($this->basePath . '/vendor/composer/installed.json', json_encode([
            'packages' => [
                [
                    'name' => 'winter/test-package',
                    'version' => '1.0.0',
                    'extra' => ['laravel' => ['providers' => $providers]],
                ],
            ],
        ]));
    }

    protected function makeApplication(): Application
    {
        $app = new Application($this->basePath);
        $app->instance('config', new Repository([
            'app' => ['providers' => [], 'loadDiscoveredPackages' => true],
        ]));

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);

        return $app;
    }
}

class CachedPackageServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->instance('cached-package', true);
    }
}
