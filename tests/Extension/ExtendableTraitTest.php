<?php

namespace Winter\Storm\Tests\Extension;

use TestCase;
use Winter\Storm\Extension\Extendable;
use Winter\Storm\Extension\ExtendableTrait;

/**
 * @testdox Extendable Trait (\Winter\Storm\Extension\ExtendableTrait)
 * @package Winter\Storm\Tests\Extension
 */
class ExtendableTraitTest extends TestCase
{
    /**
     * @testdox won't return a parent class for classes that extend \Winter\Storm\Extension\Extendable
     *
     * We don't want to return parent classes that also use the extension framework, because these will infinitely loop
     * back to the extension architecture.
     */
    public function testDontGetExtensionParentClassOnExtendable()
    {
        $extendable = new ExtendableRoot;
        $level1Extendable = new Level1Extendable;
        $level2Extendable = new Level2Extendable;

        $this->assertFalse($this->callProtectedMethod($extendable, 'extensionGetParentClass'));
        $this->assertFalse($this->callProtectedMethod($level1Extendable, 'extensionGetParentClass'));
        $this->assertFalse($this->callProtectedMethod($level2Extendable, 'extensionGetParentClass'));
    }

    /**
     * @testdox will return a non-extendable parent class reflection for classes that use the \Winter\Storm\Extension\ExtendableTrait
     */
    public function testGetExtensionParentClassOnExtendableTrait()
    {
        $nonExtendable = new NonExtendableRoot;
        $level1NonExtendable = new Level1NonExtendable;
        $level2NonExtendable = new Level2NonExtendable;
        $level3NonExtendable = new Level3NonExtendable;

        $this->assertEquals(Level1NonExtendable::class, $this->callProtectedMethod($level2NonExtendable, 'extensionGetParentClass')->getName());
        $this->assertEquals(Level1NonExtendable::class, $this->callProtectedMethod($level3NonExtendable, 'extensionGetParentClass')->getName());
    }

    /**
     * @testdox will reuse the parent class reflection across calls and instances of the same class
     *
     * The parent class resolution depends only on the class, so the reflection should be resolved once per class
     * instead of being rebuilt on every magic method call.
     */
    public function testParentClassReflectionIsMemoized()
    {
        $level2 = new Level2NonExtendable;

        $first = $this->callProtectedMethod($level2, 'extensionGetParentClass');
        $second = $this->callProtectedMethod($level2, 'extensionGetParentClass');

        $this->assertInstanceOf(\ReflectionClass::class, $first);
        $this->assertSame($first, $second);

        // Instances of the same class share the resolved reflection
        $third = $this->callProtectedMethod(new Level2NonExtendable, 'extensionGetParentClass');
        $this->assertSame($first, $third);

        // Subclasses are memoized under their own class and still resolve correctly
        $level3First = $this->callProtectedMethod(new Level3NonExtendable, 'extensionGetParentClass');
        $level3Second = $this->callProtectedMethod(new Level3NonExtendable, 'extensionGetParentClass');

        $this->assertEquals(Level1NonExtendable::class, $level3First->getName());
        $this->assertSame($level3First, $level3Second);
    }

    /**
     * @testdox routes magic methods consistently across repeated calls and instances
     */
    public function testRepeatedMagicAccessBehavesConsistently()
    {
        $a = new Level2NonExtendable;
        $b = new Level2NonExtendable;

        // No parent __get exists, so undefined properties resolve to null - repeatedly
        $this->assertNull($a->undefinedProperty);
        $this->assertNull($a->undefinedProperty);
        $this->assertNull($b->undefinedProperty);

        // Undefined methods throw consistently on every call
        foreach ([$a, $b, $a] as $instance) {
            try {
                $instance->undefinedMethod();
                $this->fail('Expected BadMethodCallException was not thrown');
            } catch (\BadMethodCallException $e) {
                $this->assertStringContainsString('undefinedMethod', $e->getMessage());
            }
        }
    }
}

class ExtendableRoot extends Extendable
{
}

class Level1Extendable extends ExtendableRoot
{
}

class Level2Extendable extends Level1Extendable
{
}

class NonExtendableRoot
{
}

class Level1NonExtendable extends NonExtendableRoot
{
}

class Level2NonExtendable extends Level1NonExtendable
{
    use ExtendableTrait;

    /**
     * @var string|array|null Extensions implemented by this class.
     */
    public $implement = null;

    /**
     * Indicates if the extendable constructor has completed.
     */
    protected bool $extendableConstructed = false;

    /**
     * This stores any locally-scoped callbacks fired before the extendable constructor had completed.
     */
    protected array $localCallbacks = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->extendableConstruct();
        $this->extendableConstructed = true;
    }

    public function __get($name)
    {
        return $this->extendableGet($name);
    }

    public function __set($name, $value)
    {
        $this->extendableSet($name, $value);
    }

    public function __call($name, $params)
    {
        return $this->extendableCall($name, $params);
    }

    public static function __callStatic($name, $params)
    {
        return static::extendableCallStatic($name, $params);
    }
}

class Level3NonExtendable extends Level2NonExtendable
{
}
