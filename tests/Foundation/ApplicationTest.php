<?php

use Winter\Storm\Foundation\Application;
use Winter\Storm\Filesystem\PathResolver;

class ApplicationTest extends TestCase
{
    protected Application $fakeApp;
    protected string $basePath;

    protected function setUp(): void
    {
        // Mock application
        $this->basePath = '/tmp/custom-path';
        $this->fakeApp = new Application($this->basePath);
    }

    public function testPathMethods()
    {
        $this->assertEquals(
            PathResolver::join($this->basePath, '/plugins'),
            $this->fakeApp->pluginsPath()
        );
        $this->assertEquals(
            PathResolver::join($this->basePath, '/themes'),
            $this->fakeApp->themesPath()
        );
        $this->assertEquals(
            PathResolver::join($this->basePath, '/storage/temp'),
            $this->fakeApp->tempPath()
        );
        $this->assertEquals(
            PathResolver::join($this->basePath, '/storage/app/uploads'),
            $this->fakeApp->uploadsPath()
        );
        $this->assertEquals(
            PathResolver::join($this->basePath, '/storage/app/media'),
            $this->fakeApp->mediaPath()
        );

        $storagePath = $this->basePath . '/storage';

        $this->assertEquals(
            PathResolver::join($storagePath, '/framework/production.config.php'),
            $this->fakeApp->getCachedConfigPath()
        );
        $this->assertEquals(
            PathResolver::join($storagePath, '/framework/routes.php'),
            $this->fakeApp->getCachedRoutesPath()
        );
        $this->assertEquals(
            PathResolver::join($storagePath, '/framework/compiled.php'),
            $this->fakeApp->getCachedCompilePath()
        );
        $this->assertEquals(
            PathResolver::join($storagePath, '/framework/services.php'),
            $this->fakeApp->getCachedServicesPath()
        );
        $this->assertEquals(
            PathResolver::join($storagePath, '/framework/packages.php'),
            $this->fakeApp->getCachedPackagesPath()
        );
        $this->assertEquals(
            PathResolver::join($storagePath, '/framework/classes.php'),
            $this->fakeApp->getCachedClassesPath()
        );
    }

    public function testCachedConfigPath()
    {
        $storagePath = $this->basePath . '/storage';

        // No env set
        $this->assertEquals(
            PathResolver::join($storagePath, '/framework/production.config.php'),
            $this->fakeApp->getCachedConfigPath()
        );

        // Test that setting the app env to each value results in the correct config file being returned
        foreach (['test', 'prod', 'local', 'dev'] as $env) {
            $this->fakeApp->detectEnvironment(fn() => $env);
            $this->assertEquals(
                PathResolver::join($storagePath, '/framework/' . $env . '.config.php'),
                $this->fakeApp->getCachedConfigPath()
            );
        }
    }

    public function testSetPathMethods()
    {
        foreach (['plugins', 'themes', 'temp', 'uploads', 'media'] as $type) {
            $getter = $type . 'Path';
            $setter = 'set' . ucfirst($type) . 'Path';

            $path = PathResolver::join('/my'.ucfirst($type), '/custom/path');
            $expected = PathResolver::standardize($path);
            $this->fakeApp->{$setter}($path);

            $this->assertEquals($expected, $this->fakeApp->{$getter}());
        }
    }
}
