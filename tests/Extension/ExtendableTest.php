<?php

use Winter\Storm\Extension\Extendable;
use Winter\Storm\Extension\ExtensionBase;
use Winter\Storm\Filesystem\Filesystem;
use Winter\Storm\Support\ClassLoader;
use Winter\Storm\Support\Testing\MocksClassLoader;

class ExtendableTest extends TestCase
{
    use MocksClassLoader;

    public function setUp(): void
    {
        parent::setUp();

        $this->registerMockClassLoader();

        $this->classLoader->addDirectories([
            'plugins'
        ]);

        $this->classLoader->addNamespaceAliases([
            'Real\\ExtendableTest' => 'Alias\\ExtendableTest',
            'Real' => 'Alias',
        ]);
    }

    public function testExtendingExtendableClass()
    {
        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClass::class);
        $this->assertNull($subject->classAttribute);

        ExtendableTestExampleExtendableClass::extend(function ($extension) {
            $extension->classAttribute = 'bar';
        });

        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClass::class);
        $this->assertEquals('bar', $subject->classAttribute);
    }

    public function testSettingDeclaredPropertyOnClass()
    {
        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClass::class);
        $subject->classAttribute = 'Test';
        $this->assertEquals('Test', $subject->classAttribute);
    }

    public function testSettingUndeclaredPropertyOnClass()
    {
        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClass::class);
        $subject->newAttribute = 'Test';
        $this->assertNull($subject->newAttribute);
        $this->assertFalse(property_exists($subject, 'newAttribute'));
    }

    public function testSettingDeclaredPropertyOnBehavior()
    {
        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClass::class);
        $behavior = $subject->getClassExtension('ExtendableTestExampleBehaviorClass1');

        $subject->behaviorAttribute = 'Test';
        $this->assertEquals('Test', $subject->behaviorAttribute);
        $this->assertEquals('Test', $behavior->behaviorAttribute);
        $this->assertTrue($subject->isClassExtendedWith('ExtendableTestExampleBehaviorClass1'));
    }

    public function testDynamicPropertyOnClass()
    {
        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClass::class);
        $this->assertFalse(property_exists($subject, 'newAttribute'));
        $subject->addDynamicProperty('dynamicAttribute', 'Test');
        $this->assertEquals('Test', $subject->dynamicAttribute);
        $this->assertTrue(property_exists($subject, 'dynamicAttribute'));
    }

    public function testDynamicallyExtendingClass()
    {
        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClass::class);
        $subject->extendClassWith('ExtendableTestExampleBehaviorClass2');

        $this->assertTrue($subject->isClassExtendedWith('ExtendableTestExampleBehaviorClass1'));
        $this->assertTrue($subject->isClassExtendedWith('ExtendableTestExampleBehaviorClass2'));
    }

    public function testDynamicMethodOnClass()
    {
        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClass::class);
        $subject->addDynamicMethod('getFooAnotherWay', 'getFoo', 'ExtendableTestExampleBehaviorClass1');

        $this->assertEquals('foo', $subject->getFoo());
        $this->assertEquals('foo', $subject->getFooAnotherWay());
    }

    public function testDynamicExtendAndMethodOnClass()
    {
        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClass::class);
        $subject->extendClassWith('ExtendableTestExampleBehaviorClass2');
        $subject->addDynamicMethod('getOriginalFoo', 'getFoo', 'ExtendableTestExampleBehaviorClass1');

        $this->assertTrue($subject->isClassExtendedWith('ExtendableTestExampleBehaviorClass1'));
        $this->assertTrue($subject->isClassExtendedWith('ExtendableTestExampleBehaviorClass2'));
        $this->assertEquals('bar', $subject->getFoo());
        $this->assertEquals('foo', $subject->getOriginalFoo());
    }

    public function testExtendOnClassWithClassLoaderAliases()
    {
        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClassAlias1::class);
        $this->assertTrue($subject->isClassExtendedWith('Real.ExtendableTest.ExampleBehaviorClass1'));

        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClassAlias2::class);
        $this->assertTrue($subject->isClassExtendedWith('Real.ExampleBehaviorClass1'));
    }

    public function testDynamicClosureOnClass()
    {
        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClass::class);
        $subject->addDynamicMethod('sayHello', function () {
            return 'Hello world';
        });

        $this->assertEquals('Hello world', $subject->sayHello());
    }

    public function testDynamicCallableOnClass()
    {
        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClass::class);
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
        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClass::class);
        $this->assertEmpty($subject->protectedFoo);

        $subject->protectedFoo = 'snickers';
        $this->assertEquals('bar', $subject->getProtectedFooAttribute());
    }

    public function testAccessingProtectedMethod()
    {
        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClass::class);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Call to undefined method ' . get_class($subject) . '::protectedBar()');

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
        $result = $this->mockClassLoader(ExtendableTestExampleExtendableSoftImplementFakeClass::class);
        $this->assertFalse($result->isClassExtendedWith('RabbleRabbleRabble'));
        $this->assertEquals('working', $result->getStatus());
    }

    public function testSoftImplementReal()
    {
        $result = $this->mockClassLoader(ExtendableTestExampleExtendableSoftImplementRealClass::class);
        $this->assertTrue($result->isClassExtendedWith('ExtendableTestExampleBehaviorClass1'));
        $this->assertEquals('foo', $result->getFoo());
    }

    public function testSoftImplementCombo()
    {
        $result = $this->mockClassLoader(ExtendableTestExampleExtendableSoftImplementComboClass::class);
        $this->assertFalse($result->isClassExtendedWith('RabbleRabbleRabble'));
        $this->assertTrue($result->isClassExtendedWith('ExtendableTestExampleBehaviorClass1'));
        $this->assertTrue($result->isClassExtendedWith('ExtendableTestExampleBehaviorClass2'));
        $this->assertEquals('bar', $result->getFoo()); // ExtendableTestExampleBehaviorClass2 takes priority, defined last
    }

    public function testDotNotation()
    {
        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClassDotNotation::class);
        $subject->extendClassWith('ExtendableTest.ExampleBehaviorClass2');

        $this->assertTrue($subject->isClassExtendedWith('ExtendableTest.ExampleBehaviorClass1'));
        $this->assertTrue($subject->isClassExtendedWith('ExtendableTest.ExampleBehaviorClass2'));
    }

    public function testMethodExists()
    {
        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClass::class);
        $this->assertTrue($subject->methodExists('extend'));
    }

    public function testMethodNotExists()
    {
        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClass::class);
        $this->assertFalse($subject->methodExists('missingFunction'));
    }

    public function testDynamicMethodExists()
    {
        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClass::class);
        $subject->addDynamicMethod('getFooAnotherWay', 'getFoo', 'ExtendableTestExampleBehaviorClass1');

        $this->assertTrue($subject->methodExists('getFooAnotherWay'));
    }

    public function testGetClassMethods()
    {
        $subject = $this->mockClassLoader(ExtendableTestExampleExtendableClass::class);
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
