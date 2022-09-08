<?php namespace Winter\Storm\Extension;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use BadMethodCallException;
use Winter\Storm\Support\ClassLoader;
use Winter\Storm\Support\Serialization;
use Illuminate\Support\Facades\App;

/**
 * Extendable trait.
 *
 * Provides dynamic class extension functionality, allowing for classes to have additional methods, properties and
 * functionality defined at runtime.
 *
 * This trait can be used when a class is unable to extend the `Extendable` class.
 *
 * @author Alexey Bobkov, Samuel Georges
 */

trait ExtendableTrait
{
    /**
     * Class reflection information, including behaviors.
     */
    protected array $extensionData = [
        'extensions'        => [],
        'methods'           => [],
        'dynamicMethods'    => [],
        'dynamicProperties' => []
    ];

    /**
     * Registered extension callbacks. These are run prior to loading implemented behaviors.
     */
    protected static array $preBehaviorCallbacks = [];

    /**
     * Registered extension callbacks. These are run after loading implemented behaviors.
     */
    protected static array $postBehaviorCallbacks = [];

    /**
     * Collection of static methods used by behaviors.
     */
    protected static array $extendableStaticMethods = [];

    /**
     * Indicates if dynamic properties can be created.
     */
    protected static bool $extendableGuardProperties = true;

    /**
     * Class loader instance.
     */
    protected static ?ClassLoader $extendableClassLoader = null;

    /**
     * This method should be called as part of the constructor.
     */
    public function extendableConstruct(): void
    {
        /*
         * Apply init callbacks
         */
        $classes = array_merge([get_class($this)], class_parents($this));

        foreach ($classes as $class) {
            if (isset(self::$preBehaviorCallbacks[$class]) && is_array(self::$preBehaviorCallbacks[$class])) {
                foreach (self::$preBehaviorCallbacks[$class] as $callback) {
                    call_user_func(Serialization::unwrapClosure($callback), $this);
                }
            }
        }

        /*
         * Apply extensions
         */
        if (!$this->implement) {
            return;
        }

        if (is_string($this->implement)) {
            $uses = explode(',', $this->implement);
        } elseif (is_array($this->implement)) {
            $uses = $this->implement;
        } else {
            throw new Exception(sprintf('Class %s contains an invalid $implement value', get_class($this)));
        }

        foreach ($uses as $use) {
            $useClass = $this->extensionNormalizeClassName($use);

            /*
             * Soft implement
             */
            if (substr($useClass, 0, 1) == '@') {
                $useClass = substr($useClass, 1);
                if (!class_exists($useClass)) {
                    continue;
                }
            }

            $this->extendClassWith($useClass);
        }

        /*
         * Apply init callbacks after Behaviors have been loaded.
         */
        foreach ($classes as $class) {
            if (isset(self::$postBehaviorCallbacks[$class]) && is_array(self::$postBehaviorCallbacks[$class])) {
                foreach (self::$postBehaviorCallbacks[$class] as $callback) {
                    call_user_func(Serialization::unwrapClosure($callback), $this);
                }
            }
        }
    }

    /**
     * Registers an extension callback.
     *
     * If `$after` is set to `true`, the callback will be run after the behaviors have been
     * initialised.
     */
    public static function extendableExtendCallback(callable $callback, bool $after = false): void
    {
        $class = get_called_class();
        $property = (($after) ? 'post' : 'pre') . 'BehaviorCallbacks';

        if (
            !isset(self::${$property}[$class]) ||
            !is_array(self::${$property}[$class])
        ) {
            self::${$property}[$class] = [];
        }

        self::${$property}[$class][] = Serialization::wrapClosure($callback);
    }

    /**
     * Clear the list of extended classes so they will be re-extended.
     */
    public static function clearExtendedClasses(): void
    {
        self::$preBehaviorCallbacks = [];
        self::$postBehaviorCallbacks = [];
    }

    /**
     * Normalizes the provided extension name allowing for the ClassLoader to inject aliased classes.
     */
    protected function extensionNormalizeClassName(string $name): string
    {
        $name = str_replace('.', '\\', trim($name));
        if (!is_null($this->extensionGetClassLoader()) && ($alias = $this->extensionGetClassLoader()->getAlias($name))) {
            $name = $alias;
        }
        return $name;
    }

    /**
     * Extracts the available methods from a behavior and adds it to the list of callable methods.
     */
    protected function extensionExtractMethods(string $extensionName, object $extensionObject): void
    {
        if (!method_exists($extensionObject, 'extensionIsHiddenMethod')) {
            throw new Exception(sprintf(
                'Extension %s should inherit Winter\Storm\Extension\ExtensionBase or implement Winter\Storm\Extension\ExtensionTrait.',
                $extensionName
            ));
        }

        $extensionMethods = get_class_methods($extensionName);
        foreach ($extensionMethods as $methodName) {
            if (
                $methodName == '__construct' ||
                $extensionObject->extensionIsHiddenMethod($methodName)
            ) {
                continue;
            }

            $this->extensionData['methods'][$methodName] = $extensionName;
        }
    }

    /**
     * Programmatically adds a method to the extendable class.
     */
    public function addDynamicMethod(string $name, callable $method, ?string $extension = null): void
    {
        if (
            is_string($method) &&
            $extension &&
            ($extensionObj = $this->getClassExtension($extension))
        ) {
            $method = [$extensionObj, $method];
        }
        $this->extensionData['dynamicMethods'][$name] = Serialization::wrapClosure($method);
    }

    /**
     * Programmatically adds a property to the extendable class
     */
    public function addDynamicProperty(string $name, $value = null): void
    {
        if (array_key_exists($name, $this->getDynamicProperties())) {
            return;
        }
        self::$extendableGuardProperties = false;

        if (!property_exists($this, $name)) {
            $this->{$name} = $value;
        }

        $this->extensionData['dynamicProperties'][] = $name;

        self::$extendableGuardProperties = true;
    }

    /**
     * Dynamically extend a class with a specified behavior.
     */
    public function extendClassWith(string $extensionName): void
    {
        if (empty($extensionName)) {
            throw new Exception(sprintf(
                'You must provide an extension name to extend class %s with.',
                get_class($this)
            ));
        }

        $extensionName = $this->extensionNormalizeClassName($extensionName);

        if (isset($this->extensionData['extensions'][$extensionName])) {
            throw new Exception(sprintf(
                'Class %s has already been extended with %s',
                get_class($this),
                $extensionName
            ));
        }

        $this->extensionData['extensions'][$extensionName] = $extensionObject = new $extensionName($this);
        $this->extensionExtractMethods($extensionName, $extensionObject);
        $extensionObject->extensionApplyInitCallbacks();
    }

    /**
     * Check if extendable class is extended with a behavior object.
     */
    public function isClassExtendedWith(string $name): bool
    {
        return isset($this->extensionData['extensions'][$this->extensionNormalizeClassName($name)]);
    }

    /**
     * Returns a behavior object from an extendable class.
     *
     * If this behavior has not been implemented in this class, this method will return `null`.
     */
    public function getClassExtension(string $name): ?object
    {
        return $this->extensionData['extensions'][$this->extensionNormalizeClassName($name)] ?? null;
    }

    /**
     * Short hand for `getClassExtension()` method, except takes the short
     * extension name, example:
     *
     *     $this->asExtension('FormController')
     */
    public function asExtension(string $shortName): ?object
    {
        $hints = [];
        foreach ($this->extensionData['extensions'] as $class => $obj) {
            if (
                preg_match('@\\\\([\w]+)$@', $class, $matches) &&
                $matches[1] == $shortName
            ) {
                return $obj;
            }
        }

        return $this->getClassExtension($shortName);
    }

    /**
     * Checks if a method exists.
     *
     * Functionally similar to the `method_exists` PHP function, but also checks for dynamic methods.
     */
    public function methodExists(string $name): bool
    {
        return (
            method_exists($this, $name) ||
            isset($this->extensionData['methods'][$name]) ||
            isset($this->extensionData['dynamicMethods'][$name])
        );
    }

    /**
     * Get a list of class methods.
     *
     * Functionally similar to the `get_class_methods` PHP function, but also returns dynamic methods.
     */
    public function getClassMethods(): array
    {
        return array_values(array_unique(array_merge(
            get_class_methods($this),
            array_keys($this->extensionData['methods']),
            array_keys($this->extensionData['dynamicMethods'])
        )));
    }

    /**
     * Returns all dynamic properties and their values.
     */
    public function getDynamicProperties(): array
    {
        $result = [];
        $propertyNames = $this->extensionData['dynamicProperties'];
        foreach ($propertyNames as $propName) {
            $result[$propName] = $this->{$propName};
        }
        return $result;
    }

    /**
     * Checks if a property exists.
     *
     * Functionally similar to the `property_exists` PHP function, but also returns dynamic properties.
     */
    public function propertyExists(string $name): bool
    {
        if (property_exists($this, $name)) {
            return true;
        }

        foreach ($this->extensionData['extensions'] as $extensionObject) {
            if (
                property_exists($extensionObject, $name) &&
                $this->extendableIsAccessible($extensionObject, $name)
            ) {
                return true;
            }
        }

        return array_key_exists($name, $this->getDynamicProperties());
    }

    /**
     * Checks if a property is accessible (public).
     */
    protected function extendableIsAccessible(object $class, string $propertyName): bool
    {
        $reflector = new ReflectionClass($class);
        $property = $reflector->getProperty($propertyName);
        return $property->isPublic();
    }

    /**
     * Magic method for `__get()`
     */
    public function extendableGet(string $name): mixed
    {
        foreach ($this->extensionData['extensions'] as $extensionObject) {
            if (
                property_exists($extensionObject, $name) &&
                $this->extendableIsAccessible($extensionObject, $name)
            ) {
                return $extensionObject->{$name};
            }
        }

        $parent = get_parent_class();
        if ($parent !== false && method_exists($parent, '__get')) {
            return parent::__get($name);
        }

        return null;
    }

    /**
     * Magic method for `__set()`
     */
    public function extendableSet(string $name, $value = null): void
    {
        foreach ($this->extensionData['extensions'] as $extensionObject) {
            if (!property_exists($extensionObject, $name)) {
                continue;
            }

            $extensionObject->{$name} = $value;
        }

        /*
         * This targets trait usage in particular
         */
        $parent = get_parent_class();
        if ($parent !== false && method_exists($parent, '__set')) {
            parent::__set($name, $value);
        }

        /*
         * Setting an undefined property
         */
        if (!self::$extendableGuardProperties) {
            $this->{$name} = $value;
        }
    }

    /**
     * Magic method for `__call()`
     */
    public function extendableCall(string $name, array $params = [])
    {
        if (isset($this->extensionData['methods'][$name])) {
            $extension = $this->extensionData['methods'][$name];
            $extensionObject = $this->extensionData['extensions'][$extension];

            if (method_exists($extension, $name)) {
                return call_user_func_array([$extensionObject, $name], array_values($params));
            }
        }

        if (isset($this->extensionData['dynamicMethods'][$name])) {
            $dynamicCallable = $this->extensionData['dynamicMethods'][$name];

            if (is_callable($dynamicCallable)) {
                return call_user_func_array(Serialization::unwrapClosure($dynamicCallable), array_values($params));
            }
        }

        $parent = get_parent_class();
        if ($parent !== false && method_exists($parent, '__call')) {
            return parent::__call($name, $params);
        }

        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()',
            get_class($this),
            $name
        ));
    }

    /**
     * Magic method for `__callStatic()`
     */
    public static function extendableCallStatic(string $name, array $params = [])
    {
        $className = get_called_class();

        if (!array_key_exists($className, self::$extendableStaticMethods)) {
            self::$extendableStaticMethods[$className] = [];

            $class = new ReflectionClass($className);
            $defaultProperties = $class->getDefaultProperties();
            if (
                array_key_exists('implement', $defaultProperties) &&
                ($implement = $defaultProperties['implement'])
            ) {
                /*
                 * Apply extensions
                 */
                if (is_string($implement)) {
                    $uses = explode(',', $implement);
                }
                elseif (is_array($implement)) {
                    $uses = $implement;
                }
                else {
                    throw new Exception(sprintf('Class %s contains an invalid $implement value', $className));
                }

                foreach ($uses as $use) {
                    // Class alias checks not required here as the current name of the extension class doesn't
                    // matter because as long as $useClassName is able to be instantiated the method will resolve
                    $useClassName = str_replace('.', '\\', trim($use));

                    $useClass = new ReflectionClass($useClassName);
                    $staticMethods = $useClass->getMethods(ReflectionMethod::IS_STATIC);
                    foreach ($staticMethods as $method) {
                        self::$extendableStaticMethods[$className][$method->getName()] = $useClassName;
                    }
                }
            }
        }

        if (isset(self::$extendableStaticMethods[$className][$name])) {
            $extension = self::$extendableStaticMethods[$className][$name];

            if (method_exists($extension, $name) && is_callable([$extension, $name])) {
                $extension::$extendableStaticCalledClass = $className;
                $result = forward_static_call_array(array($extension, $name), $params);
                $extension::$extendableStaticCalledClass = null;
                return $result;
            }
        }

        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()',
            $className,
            $name
        ));
    }

    /**
     * Gets the class loader instance.
     */
    protected function extensionGetClassLoader(): ?ClassLoader
    {
        if (!is_null(self::$extendableClassLoader)) {
            return self::$extendableClassLoader;
        }

        if (!class_exists('App')) {
            return null;
        }

        return self::$extendableClassLoader = App::make(ClassLoader::class);
    }
}
