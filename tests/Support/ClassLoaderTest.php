<?php

use Winter\Storm\Filesystem\Filesystem;
use Winter\Storm\Support\ClassLoader;

class ClassLoaderTest extends TestCase
{
    /** @var ClassLoader */
    protected $classLoader;

    public function setUp(): void
    {
        parent::setUp();

        $this->classLoader = new ClassLoader(
            new Filesystem(),
            dirname(__DIR__) . '/fixtures/classes',
            dirname(__DIR__) . '/fixtures/classes/classes.php'
        );

        $this->classLoader->register();

        $this->classLoader->addDirectories([
            'plugins'
        ]);
    }

    public function tearDown(): void
    {
        $this->classLoader->unregister();

        parent::tearDown();
    }

    public function testClassesExist()
    {
        // Classes should be available from the class loader
        $this->assertTrue(class_exists('Winter\Plugin\Classes\TestClass'));
        $this->assertTrue(class_exists('Winter\Plugin\Models\TestModel'));

        // Classes should not be available from the class loader (missing classes)
        $this->assertFalse(class_exists('Winter\Plugin\Classes\MissingClass'));
        $this->assertFalse(class_exists('Winter\Plugin\Widgets\MissingWidget'));

        // Class should not be available from the class loader (misnamed namespace)
        $this->assertFalse(class_exists('Winter\Plugin\Controllers\TestController'));
    }

    public function testAliases()
    {
        $this->assertFalse(class_exists('OldOrg\Plugin\Classes\TestClass'));
        $this->assertFalse(class_exists('OldOrg\Plugin\Models\TestModel'));

        // Alias missing classes
        $this->classLoader->addAliases([
            'Winter\Plugin\Classes\TestClass' => 'OldOrg\Plugin\Classes\TestClass',
            'Winter\Plugin\Models\TestModel' => 'OldOrg\Plugin\Models\TestModel',
        ]);

        $this->assertTrue(class_exists('OldOrg\Plugin\Classes\TestClass'));
        $this->assertTrue(class_exists('OldOrg\Plugin\Models\TestModel'));

        $instance = new OldOrg\Plugin\Classes\TestClass;
        $this->assertInstanceOf('Winter\Plugin\Classes\TestClass', $instance);

        // Alias a class that exists - the original should still be used
        $this->classLoader->addAliases([
            'NewOrg\Plugin\Classes\TestClass' => 'Winter\Plugin\Classes\TestClass',
        ]);

        $instance = new Winter\Plugin\Classes\TestClass;
        $this->assertInstanceOf('Winter\Plugin\Classes\TestClass', $instance);
        $this->assertFalse(class_exists('NewOrg\Plugin\Classes\TestClass'));
    }
}
