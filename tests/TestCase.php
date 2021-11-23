<?php

use PHPUnit\Framework\Assert;
use Winter\Storm\Foundation\Application;
use Winter\Storm\Support\Facade;

class TestCase extends PHPUnit\Framework\TestCase
{
    /**
     * Instance of a test Application.
     *
     * @var \Winter\Storm\Foundation\Application
     */
    public $app = null;

    protected function tearDown(): void
    {
        if (!is_null($this->app)) {
            Facade::clearResolvedInstances();
            unset($this->app);
        }

        parent::tearDown();
    }

    /**
     * Creates a basic Application instance for using facades.
     *
     * @return void
     */
    protected function createApplication(): void
    {
        if (!is_null($this->app)) {
            return;
        }

        $this->app = new Application('/tmp/custom-path');

        // Set facades to use this testing app
        Facade::setFacadeApplication($this->app);
    }

    /**
     * Helper method to call a protected method in a class.
     *
     * @param object $object
     * @param string $name
     * @param array $params
     * @return mixed
     */
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
}
