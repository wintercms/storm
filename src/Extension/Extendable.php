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
 * @method mixed extend(callable $callback, ?object $outerScope = null)
 * @method static void extend(callable $callback, bool $scoped = false, ?object $outerScope = null)
 * @author Alexey Bobkov, Samuel Georges
 */
class Extendable
{
    use ExtendableTrait;

    /**
     * @var string|array|null Extensions implemented by this class.
     */
    public $implement = null;

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
            if (empty($params[0]) || !is_callable($params[0])) {
                throw new \InvalidArgumentException('The extend() method requires a callback parameter or closure.');
            }
            if ($params[0] instanceof \Closure) {
                return $this->extendableAddLocalExtension($params[0], $params[1] ?? null);
            }
            return $this->extendableAddLocalExtension(\Closure::fromCallable($params[0]), $params[1] ?? false);
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

    /**
     * Adds local extensibility to the current instance.
     *
     * This rebinds a given closure to the current instance, making it able to access protected and private methods. This
     * makes any call using `$this` within the closure act on the extended class, not the class providing the extension.
     *
     * An outer scope may be provided by providing a second parameter, which will then be passed through to the closure
     * as its first parameter. If this is not given, the current instance will be provided as the first parameter.
     */
    protected function extendableAddLocalExtension(\Closure $callback, ?object $outerScope = null)
    {
        return $callback->call($this, $outerScope ?? $this);
    }
}
