<?php namespace Winter\Storm\Extension;

/**
 * Extension class
 *
 * If a class extends this class, it will enable support for using "Private traits".
 *
 * Usage:
 *
 *     public $implement = ['Path.To.Some.Namespace.Class'];
 *
 * See the `ExtensionBase` class for creating extension classes.
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class Extendable
{
    use ExtendableTrait;

    /**
     * @var array Extensions implemented by this class.
     */
    public $implement;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->extendableConstruct();
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
        if ($name === 'extend') {
            if (empty($params[0])) {
                throw new \InvalidArgumentException('The extend() method requires a callback parameter or closure.');
            }
            return $this->extendableAddLocalExtension($params[0]);
        }

        return $this->extendableCall($name, $params);
    }

    public static function __callStatic($name, $params)
    {
        if ($name === 'extend') {
            if (empty($params[0])) {
                throw new \InvalidArgumentException('The extend() method requires a callback parameter or closure.');
            }
            static::extendableAddExtension($params[0], $params[1] ?? false, $params[2] ?? null);
            return;
        }

        return static::extendableCallStatic($name, $params);
    }

    /**
     * Extends the class using a closure.
     *
     * The closure will be provided a single parameter which is the instance of the extended class, by default.
     *
     * You may optionally specify the callback as a scoped callback, which inherits the scope of the extended class and
     * provides access to protected and private methods and properties. This makes any call using `$this` act on the
     * extended class, not the class providing the extension.
     *
     * If you use a scoped callback, you can provide the "outer" scope - or the scope of the class providing the extension,
     * with the third parameter. The outer scope object will then be passed as the single parameter to the closure.
     */
    public static function extendableAddExtension(callable $callback, bool $scoped = false, ?object $outerScope = null): void
    {
        static::extendableExtendCallback($callback, $scoped, $outerScope);
    }

    protected function extendableAddLocalExtension(callable $callback)
    {
        return $callback->call($this, $this);
    }
}
