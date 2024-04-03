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
