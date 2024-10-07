<?php namespace Winter\Storm\Support\Testing;

use Winter\Storm\Filesystem\Filesystem;
use Winter\Storm\Support\ClassLoader;

/**
 * Helper trait to test classes which use the ClassLoader (ie. extensions).
 *
 * This trait will mock the ClassLoader so that it does not require the entire app to be instantiated.
 */
trait MocksClassLoader
{
    /** @var ClassLoader Mocked ClassLoader instance */
    protected $classLoader;

    /**
     * Registers a mock class loader in the autoload stack.
     *
     * @param string $basePath
     * @param string $manifestPath
     * @return void
     */
    protected function registerMockClassLoader($basePath = null, $manifestPath = null)
    {
        if (is_null($basePath)) {
            $basePath = dirname(dirname(__DIR__)) . '/fixtures/classes';
        }
        if (is_null($manifestPath)) {
            $manifestPath = dirname(dirname(__DIR__)) . '/fixtures/classes/classes.php';
        }

        $this->classLoader = new ClassLoader(
            new Filesystem(),
            $basePath,
            $manifestPath
        );

        $this->classLoader->register();
    }

    /**
     * Mocks the `getClassLoader` method of a class that uses the `Extendable` trait.
     *
     * The mock will use the class loader from this trait.
     *
     * @param string $class
     * @return mixed
     */
    protected function mockClassLoader($class)
    {
        $subject = $this->getMockBuilder($class)
            ->onlyMethods(['extensionGetClassLoader'])
            ->disableOriginalConstructor()
            ->getMock();

        $subject->expects($this->any())
            ->method('extensionGetClassLoader')
            ->will($this->returnValue($this->classLoader));

        // Run construction
        $subject->__construct();

        return $subject;
    }
}
