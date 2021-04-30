<?php

use Winter\Storm\Extension\Extendable;
use Winter\Storm\Extension\ExtensionBase;
use Winter\Storm\Filesystem\Filesystem;
use Winter\Storm\Support\ClassLoader;

class ExtendableTest extends TestCase
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

        $this->classLoader->addNamespaceAliases([
            'Real\\ExtendableTest' => 'Alias\\ExtendableTest',
            'Real' => 'Alias',
        ]);
    }

    /**
     * Returns a class with mocked `getClassLoader` method to allow for `ClassLoader` injection
     *
     * @param string $class
     * @return mixed
     */
    protected function getExtendableClass($class)
    {
        $subject = $this->getMockBuilder($class)
            ->setMethods(['extensionGetClassLoader'])
            ->getMock();

        $subject->expects($this->any())
            ->method('extensionGetClassLoader')
            ->will($this->returnValue($this->classLoader));

        return $subject;
    }

    public function testExtendingExtendableClass()
    {
        $subject = new ExtendableTestExampleExtendableClass;
        $this->assertNull($subject->classAttribute);

        ExtendableTestExampleExtendableClass::extend(function ($extension) {
            $extension->classAttribute = 'bar';
        });

        $subject = new ExtendableTestExampleExtendableClass;
        $this->assertEquals('bar', $subject->classAttribute);
    }

    public function testSettingDeclaredPropertyOnClass()
    {
        $subject = new ExtendableTestExampleExtendableClass;
        $subject->classAttribute = 'Test';
        $this->assertEquals('Test', $subject->classAttribute);
    }

    public function testSettingUndeclaredPropertyOnClass()
    {
        $subject = new ExtendableTestExampleExtendableClass;
        $subject->newAttribute = 'Test';
        $this->assertNull($subject->newAttribute);
        $this->assertFalse(property_exists($subject, 'newAttribute'));
    }

    public function testSettingDeclaredPropertyOnBehavior()
    {
        $subject = new ExtendableTestExampleExtendableClass;
        $behavior = $subject->getClassExtension('ExtendableTestExampleBehaviorClass1');

        $subject->behaviorAttribute = 'Test';
        $this->assertEquals('Test', $subject->behaviorAttribute);
        $this->assertEquals('Test', $behavior->behaviorAttribute);
        $this->assertTrue($subject->isClassExtendedWith('ExtendableTestExampleBehaviorClass1'));
    }

    public function testDynamicPropertyOnClass()
    {
        $subject = new ExtendableTestExampleExtendableClass;
        $this->assertFalse(property_exists($subject, 'newAttribute'));
        $subject->addDynamicProperty('dynamicAttribute', 'Test');
        $this->assertEquals('Test', $subject->dynamicAttribute);
        $this->assertTrue(property_exists($subject, 'dynamicAttribute'));
    }

    public function testDynamicallyExtendingClass()
    {
        $subject = new ExtendableTestExampleExtendableClass;
        $subject->extendClassWith('ExtendableTestExampleBehaviorClass2');

        $this->assertTrue($subject->isClassExtendedWith('ExtendableTestExampleBehaviorClass1'));
        $this->assertTrue($subject->isClassExtendedWith('ExtendableTestExampleBehaviorClass2'));
    }

    public function testDynamicMethodOnClass()
    {
        $subject = new ExtendableTestExampleExtendableClass;
        $subject->addDynamicMethod('getFooAnotherWay', 'getFoo', 'ExtendableTestExampleBehaviorClass1');

        $this->assertEquals('foo', $subject->getFoo());
        $this->assertEquals('foo', $subject->getFooAnotherWay());
    }

    public function testDynamicExtendAndMethodOnClass()
    {
        $subject = new ExtendableTestExampleExtendableClass;
        $subject->extendClassWith('ExtendableTestExampleBehaviorClass2');
        $subject->addDynamicMethod('getOriginalFoo', 'getFoo', 'ExtendableTestExampleBehaviorClass1');

        $this->assertTrue($subject->isClassExtendedWith('ExtendableTestExampleBehaviorClass1'));
        $this->assertTrue($subject->isClassExtendedWith('ExtendableTestExampleBehaviorClass2'));
        $this->assertEquals('bar', $subject->getFoo());
        $this->assertEquals('foo', $subject->getOriginalFoo());
    }

    public function testExtendOnClassWithClassLoaderAliases()
    {
        $subject = $this->getExtendableClass(ExtendableTestExampleExtendableClassAlias1::class);
        $this->assertTrue($subject->isClassExtendedWith('Real.ExtendableTest.ExampleBehaviorClass1'));

        $subject = $this->getExtendableClass(ExtendableTestExampleExtendableClassAlias2::class);
        $this->assertTrue($subject->isClassExtendedWith('Real.ExampleBehaviorClass1'));
    }

    public function testDynamicClosureOnClass()
    {
        $subject = new ExtendableTestExampleExtendableClass;
        $subject->addDynamicMethod('sayHello', function () {
            return 'Hello world';
        });

        $this->assertEquals('Hello world', $subject->sayHello());
    }

    public function testDynamicCallableOnClass()
    {
        $subject = new ExtendableTestExampleExtendableClass;
        $subject->addDynamicMethod('getAppName', ['ExtendableTestExampleClass', 'getName']);

        $this->assertEquals('winter', $subject->getAppName());
    }

    public function testCallingStaticMethod()
    {
        $result = ExtendableTestExampleExtendableClass::getStaticBar();
        $this->assertEquals('bar', $result);

        $result = ExtendableTestExampleExtendableClass::vanillaIceIce();
        $this->assertEquals('baby', $result);
    }

    public function testCallingUndefinedStaticMethod()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Call to undefined method ExtendableTestExampleExtendableClass::undefinedMethod()');

        $result = ExtendableTestExampleExtendableClass::undefinedMethod();
        $this->assertEquals('bar', $result);
    }

    public function testAccessingProtectedProperty()
    {
        $subject = new ExtendableTestExampleExtendableClass;
        $this->assertEmpty($subject->protectedFoo);

        $subject->protectedFoo = 'snickers';
        $this->assertEquals('bar', $subject->getProtectedFooAttribute());
    }

    public function testAccessingProtectedMethod()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Call to undefined method ExtendableTestExampleExtendableClass::protectedBar()');

        $subject = new ExtendableTestExampleExtendableClass;
        echo $subject->protectedBar();
    }

    public function testAccessingProtectedStaticMethod()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Call to undefined method ExtendableTestExampleExtendableClass::protectedMars()');

        echo ExtendableTestExampleExtendableClass::protectedMars();
    }

    public function testInvalidImplementValue()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Class ExtendableTestInvalidExtendableClass contains an invalid $implement value');

        $result = new ExtendableTestInvalidExtendableClass;
    }

    public function testSoftImplementFake()
    {
        $result = $this->getExtendableClass(ExtendableTestExampleExtendableSoftImplementFakeClass::class);
        $this->assertFalse($result->isClassExtendedWith('RabbleRabbleRabble'));
        $this->assertEquals('working', $result->getStatus());
    }

    public function testSoftImplementReal()
    {
        $result = $this->getExtendableClass(ExtendableTestExampleExtendableSoftImplementRealClass::class);
        $this->assertTrue($result->isClassExtendedWith('ExtendableTestExampleBehaviorClass1'));
        $this->assertEquals('foo', $result->getFoo());
    }

    public function testSoftImplementCombo()
    {
        $result = $this->getExtendableClass(ExtendableTestExampleExtendableSoftImplementComboClass::class);
        $this->assertFalse($result->isClassExtendedWith('RabbleRabbleRabble'));
        $this->assertTrue($result->isClassExtendedWith('ExtendableTestExampleBehaviorClass1'));
        $this->assertTrue($result->isClassExtendedWith('ExtendableTestExampleBehaviorClass2'));
        $this->assertEquals('bar', $result->getFoo()); // ExtendableTestExampleBehaviorClass2 takes priority, defined last
    }

    public function testDotNotation()
    {
        $subject = $this->getExtendableClass(ExtendableTestExampleExtendableClassDotNotation::class);
        $subject->extendClassWith('ExtendableTest.ExampleBehaviorClass2');

        $this->assertTrue($subject->isClassExtendedWith('ExtendableTest.ExampleBehaviorClass1'));
        $this->assertTrue($subject->isClassExtendedWith('ExtendableTest.ExampleBehaviorClass2'));
    }

    public function testMethodExists()
    {
        $subject = new ExtendableTestExampleExtendableClass;
        $this->assertTrue($subject->methodExists('extend'));
    }

    public function testMethodNotExists()
    {
        $subject = new ExtendableTestExampleExtendableClass;
        $this->assertFalse($subject->methodExists('missingFunction'));
    }

    public function testDynamicMethodExists()
    {
        $subject = new ExtendableTestExampleExtendableClass;
        $subject->addDynamicMethod('getFooAnotherWay', 'getFoo', 'ExtendableTestExampleBehaviorClass1');

        $this->assertTrue($subject->methodExists('getFooAnotherWay'));
    }

    public function testGetClassMethods()
    {
        $subject = new ExtendableTestExampleExtendableClass;
        $subject->addDynamicMethod('getFooAnotherWay', 'getFoo', 'ExtendableTestExampleBehaviorClass1');
        $methods =  $subject->getClassMethods();

        $this->assertContains('extend', $methods);
        $this->assertContains('getFoo', $methods);
        $this->assertContains('getFooAnotherWay', $methods);
        $this->assertNotContains('missingFunction', $methods);
    }
}

//
// Test classes
//

/**
 * Example behavior classes
 */
class ExtendableTestExampleBehaviorClass1 extends ExtensionBase
{
    public $behaviorAttribute;

    public function getFoo()
    {
        return 'foo';
    }

    public static function getStaticBar()
    {
        return 'bar';
    }

    public static function vanillaIceIce()
    {
        return 'cream';
    }
}

class ExtendableTestExampleBehaviorClass2 extends ExtensionBase
{
    public $behaviorAttribute;

    public function getFoo()
    {
        return 'bar';
    }
}

/*
 * Example class that has an invalid implementation
 */
class ExtendableTestInvalidExtendableClass extends Extendable
{
    public $implement = 24;

    public $classAttribute;
}

/*
 * Example class that has extensions enabled
 */
class ExtendableTestExampleExtendableClass extends Extendable
{
    public $implement = ['ExtendableTestExampleBehaviorClass1'];

    public $classAttribute;

    protected $protectedFoo = 'bar';

    public static function vanillaIceIce()
    {
        return 'baby';
    }

    protected function protectedBar()
    {
        return 'foo';
    }

    protected static function protectedMars()
    {
        return 'bar';
    }

    public function getProtectedFooAttribute()
    {
        return $this->protectedFoo;
    }
}

/*
 * Example class that has extensions enabled via `ClassLoader` alias
 */
class ExtendableTestExampleExtendableClassAlias1 extends Extendable
{
    public $implement = ['Alias.ExtendableTest.ExampleBehaviorClass1'];
}

/*
 * Example class that has extensions enabled via `ClassLoader` alias
 */
class ExtendableTestExampleExtendableClassAlias2 extends Extendable
{
    public $implement = ['Alias.ExampleBehaviorClass1'];
}

/**
 * A normal class without extensions enabled
 */
class ExtendableTestExampleClass
{
    public static function getName()
    {
        return 'winter';
    }
}

/*
 * Example class with soft implement failure
 */
class ExtendableTestExampleExtendableSoftImplementFakeClass extends Extendable
{
    public $implement = ['@RabbleRabbleRabble'];

    public static function getStatus()
    {
        return 'working';
    }
}

/*
 * Example class with soft implement success
 */
class ExtendableTestExampleExtendableSoftImplementRealClass extends Extendable
{
    public $implement = ['@ExtendableTestExampleBehaviorClass1'];
}

/*
 * Example class with soft implement hybrid
 */
class ExtendableTestExampleExtendableSoftImplementComboClass extends Extendable
{
    public $implement = [
        'ExtendableTestExampleBehaviorClass1',
        '@ExtendableTestExampleBehaviorClass2',
        '@RabbleRabbleRabble'
    ];
}

/*
 * Example class that has extensions enabled using dot notation
 */
class ExtendableTestExampleExtendableClassDotNotation extends Extendable
{
    public $implement = ['ExtendableTest.ExampleBehaviorClass1'];

    public $classAttribute;

    protected $protectedFoo = 'bar';

    public static function vanillaIceIce()
    {
        return 'baby';
    }

    protected function protectedBar()
    {
        return 'foo';
    }

    protected static function protectedMars()
    {
        return 'bar';
    }

    public function getProtectedFooAttribute()
    {
        return $this->protectedFoo;
    }
}

/*
 * Add namespaced aliases for dot notation test
 */
class_alias('ExtendableTestExampleBehaviorClass1', 'ExtendableTest\\ExampleBehaviorClass1');
class_alias('ExtendableTestExampleBehaviorClass2', 'ExtendableTest\\ExampleBehaviorClass2');

/*
 * Add namespaces for `ClassLoader` aliasing
 */
class_alias('ExtendableTestExampleBehaviorClass1', 'Real\\ExtendableTest\\ExampleBehaviorClass1');
class_alias('ExtendableTestExampleBehaviorClass1', 'Real\\ExampleBehaviorClass1');
