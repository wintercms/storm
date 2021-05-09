<?php namespace Winter\Storm\Extension;

use Opis\Closure\SerializableClosure;

/**
 * Extension trait
 *
 * Allows for "Private traits"
 *
 * @author Alexey Bobkov, Samuel Georges
 */

trait ExtensionTrait
{
    /**
     * @var array Used to extend the constructor of an extension class. Eg:
     *
     *     BehaviorClass::extend(function($obj) { })
     *
     */
    protected static $extensionCallbacks = [];

    /**
     * @var string The calling class when using a static method.
     */
    public static $extendableStaticCalledClass = null;

    protected $extensionHidden = [
        ExtensionConstants::FIELDS => [],
        ExtensionConstants::METHODS => ['extensionIsHiddenField', 'extensionIsHiddenMethod']
    ];

    public function extensionApplyInitCallbacks()
    {
        $classes = array_merge([get_class($this)], class_parents($this));
        foreach ($classes as $class) {
            if (isset(self::$extensionCallbacks[$class]) && is_array(self::$extensionCallbacks[$class])) {
                foreach (self::$extensionCallbacks[$class] as $callback) {
                    if ($callback instanceof SerializableClosure) {
                        $callback = $callback->getClosure();
                    }
                    call_user_func($callback, $this);
                }
            }
        }
    }

    /**
     * Helper method for `::extend()` static method
     * @param  callable $callback
     * @return void
     */
    public static function extensionExtendCallback($callback)
    {
        $class = get_called_class();
        if (
            !isset(self::$extensionCallbacks[$class]) ||
            !is_array(self::$extensionCallbacks[$class])
        ) {
            self::$extensionCallbacks[$class] = [];
        }
        if ($callback instanceof \Closure && !($callback instanceof SerializableClosure)) {
            $callback = new SerializableClosure(($callback));
        }
        self::$extensionCallbacks[$class][] = $callback;
    }

    protected function extensionHideField($name)
    {
        $this->extensionHidden[ExtensionConstants::FIELDS][] = $name;
    }

    protected function extensionHideMethod($name)
    {
        $this->extensionHidden[ExtensionConstants::METHODS][] = $name;
    }

    public function extensionIsHiddenField($name)
    {
        return in_array($name, $this->extensionHidden[ExtensionConstants::FIELDS]);
    }

    public function extensionIsHiddenMethod($name)
    {
        return in_array($name, $this->extensionHidden[ExtensionConstants::METHODS]);
    }

    public static function getCalledExtensionClass()
    {
        return self::$extendableStaticCalledClass;
    }
}
