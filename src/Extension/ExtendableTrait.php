<?php namespace Winter\Storm\Extension;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use BadMethodCallException;
use Closure;
use Winter\Storm\Support\ClassLoader;
use Winter\Storm\Support\Serialization;
use Illuminate\Support\Facades\App;
use ReflectionException;

/**
 * This extension trait is used when access to the underlying base class
 * is not available, such as classes that belong to the foundation
 * framework (Laravel). It is currently used by the Controller and
 * Model classes.
 *
 * @author Alexey Bobkov, Samuel Georges
 */
trait ExtendableTrait
{
    /**
     * @var array Class reflection information, including behaviors.
     */
    protected $extensionData = [
        'extensions'        => [],
        'methods'           => [],
        'dynamicMethods'    => [],
        'dynamicProperties' => []
    ];

    /**
     * @var array Used to extend the constructor of an extendable class. Eg:
     *
     *     Class::extend(function($obj) { })
     *
     */
    protected static $extendableCallbacks = [];

    /**
     * @var array Collection of static methods used by behaviors.
     */
    protected static $extendableStaticMethods = [];

    /**
     * @var bool Indicates if dynamic properties can be created.
     */
    protected static $extendableGuardProperties = true;

    /**
     * @var ClassLoader|null Class loader instance.
     */
    protected static $extendableClassLoader = null;

    /**
     * This method should be called as part of the constructor.
     */
    public function extendableConstruct()
    {
        /*
         * Apply init callbacks
         */
        $classes = array_merge([get_class($this)], class_parents($this));
        foreach ($classes as $class) {
            if (isset(self::$extendableCallbacks[$class]) && is_array(self::$extendableCallbacks[$class])) {
                foreach (self::$extendableCallbacks[$class] as $callback) {
                    if ($callback['scoped']) {
                        $closure = Closure::bind(Serialization::unwrapClosure($callback['closure']), $this, $this);
                        $object = $callback['outer'] ?? null;
                    } else {
                        $closure = Serialization::unwrapClosure($callback['closure']);
                        $object = $this;
                    }
                    call_user_func($closure, $object);
                }
            }
        }

        /*
         * Apply extensions
         */
        if (is_string($this->implement)) {
            $uses = explode(',', $this->implement);
        } elseif (is_array($this->implement)) {
            $uses = $this->implement;
        } else {
            return;
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
    }

    /**
     * Helper method for `::extend()` static method.
     */
    public static function extendableExtendCallback(callable $callback, bool $scoped = false, ?object $outerScope = null): void
    {
        $class = get_called_class();
        if (
            !isset(self::$extendableCallbacks[$class]) ||
            !is_array(self::$extendableCallbacks[$class])
        ) {
            self::$extendableCallbacks[$class] = [];
        }
        self::$extendableCallbacks[$class][] = [
            'closure' => Serialization::wrapClosure($callback),
            'scoped' => $scoped,
            'outer' => $outerScope,
        ];
    }

    /**
     * Clear the list of extended classes so they will be re-extended.
     * @return void
     */
    public static function clearExtendedClasses()
    {
        self::$extendableCallbacks = [];
    }

    /**
     * Normalizes the provided extension name allowing for the ClassLoader to inject aliased classes
     *
     * @param string $name
     * @return string
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
     * Extracts the available methods from a behavior and adds it to the
     * list of callable methods.
     * @param  string $extensionName
     * @param  object $extensionObject
     * @return void
     */
    protected function extensionExtractMethods($extensionName, $extensionObject)
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
     * Programmatically adds a method to the extendable class
     * @param string   $dynamicName
     * @param callable $method
     * @param string   $extension
     */
    public function addDynamicMethod($dynamicName, $method, $extension = null)
    {
        if (
            is_string($method) &&
            $extension &&
            ($extensionObj = $this->getClassExtension($extension))
        ) {
            $method = [$extensionObj, $method];
        }
        $this->extensionData['dynamicMethods'][$dynamicName] = Serialization::wrapClosure($method);
    }

    /**
     * Programmatically adds a property to the extendable class
     *
     * @param string $dynamicName The name of the property to add
     * @param mixed $value The value of the property
     * @return void
     */
    public function addDynamicProperty($dynamicName, $value = null)
    {
        if (array_key_exists($dynamicName, $this->getDynamicProperties())) {
            return;
        }
        self::$extendableGuardProperties = false;

        array_set($this->extensionData['dynamicProperties'], $dynamicName, $value);

        self::$extendableGuardProperties = true;
    }

    /**
     * Dynamically extend a class with a specified behavior
     * @param string $extensionName
     * @return void
     */
    public function extendClassWith($extensionName)
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
     * Check if extendable class is extended with a behavior object
     * @param  string $name Fully qualified behavior name
     * @return boolean
     */
    public function isClassExtendedWith($name)
    {
        return isset($this->extensionData['extensions'][$this->extensionNormalizeClassName($name)]);
    }

    /**
     * Returns a behavior object from an extendable class, example:
     *
     *     $this->getClassExtension('Backend.Behaviors.FormController')
     *
     * @param  string $name Fully qualified behavior name
     * @return mixed
     */
    public function getClassExtension($name)
    {
        return $this->extensionData['extensions'][$this->extensionNormalizeClassName($name)] ?? null;
    }

    /**
     * Short hand for `getClassExtension()` method, except takes the short
     * extension name, example:
     *
     *     $this->asExtension('FormController')
     *
     * @param  string $shortName
     * @return mixed
     */
    public function asExtension($shortName)
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
     * Checks if a method exists, extension equivalent of method_exists()
     * @param  string $name
     * @return boolean
     */
    public function methodExists($name)
    {
        return (
            method_exists($this, $name) ||
            isset($this->extensionData['methods'][$name]) ||
            isset($this->extensionData['dynamicMethods'][$name])
        );
    }

    /**
     * Get a list of class methods, extension equivalent of get_class_methods()
     * @return array
     */
    public function getClassMethods()
    {
        return array_values(array_unique(array_merge(
            get_class_methods($this),
            array_keys($this->extensionData['methods']),
            array_keys($this->extensionData['dynamicMethods'])
        )));
    }

    /**
     * Returns all dynamic properties and their values
     * @return array ['property' => 'value']
     */
    public function getDynamicProperties()
    {
        return $this->extensionData['dynamicProperties'];
    }

    /**
     * Checks if a property exists, extension equivalent of `property_exists()`
     * @param  string $name
     * @return boolean
     */
    public function propertyExists($name)
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
     * Checks if a property is accessible, property equivalent of `is_callable()`
     * @param  mixed  $class
     * @param  string $propertyName
     * @return boolean
     */
    protected function extendableIsAccessible($class, $propertyName)
    {
        $reflector = new ReflectionClass($class);
        $property = $reflector->getProperty($propertyName);
        return $property->isPublic();
    }

    /**
     * Magic method for `__get()`
     * @param string $name
     * @return mixed|null
     */
    public function extendableGet($name)
    {
        if (isset($this->extensionData['dynamicProperties'][$name])) {
            return $this->extensionData['dynamicProperties'][$name];
        }

        foreach ($this->extensionData['extensions'] as $extensionObject) {
            if (
                property_exists($extensionObject, $name) &&
                $this->extendableIsAccessible($extensionObject, $name)
            ) {
                return $extensionObject->{$name};
            }
        }

        $parent = $this->extensionGetParentClass();
        if ($parent !== false && $this->extensionMethodExists($parent, '__get')) {
            return $this->extensionCallMethod($parent, '__get', [$name]);
        }

        return null;
    }

    /**
     * Magic method for `__set()`
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function extendableSet($name, $value)
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
        $parent = $this->extensionGetParentClass();
        if ($parent !== false && $this->extensionMethodExists($parent, '__set')) {
            $this->extensionCallMethod($parent, '__set', [$name, $value]);
        }

        /*
         * Setting an undefined property
         */
        if (!self::$extendableGuardProperties) {
            $this->{$name} = $value;
        }
    }

    /**
     * Magic method for `__call()`.
     *
     * Callback priority is as follows:
     * - "Dynamic Methods" added locally to the object via addDynamicMethod($name, $callable)
     * - Methods available on Behaviors that have been implemented by the object
     * - Pass it to the parent's __call() method if it defines one
     *
     * @param  string $name
     * @param  array  $params
     * @return mixed
     */
    public function extendableCall($name, $params = null)
    {
        if (isset($this->extensionData['dynamicMethods'][$name])) {
            $dynamicCallable = $this->extensionData['dynamicMethods'][$name];

            if (is_callable($dynamicCallable)) {
                return call_user_func_array(Serialization::unwrapClosure($dynamicCallable), array_values($params));
            }
        }

        if (isset($this->extensionData['methods'][$name])) {
            $extension = $this->extensionData['methods'][$name];
            $extensionObject = $this->extensionData['extensions'][$extension];

            if (method_exists($extension, $name)) {
                return call_user_func_array([$extensionObject, $name], array_values($params));
            }
        }

        $parent = $this->extensionGetParentClass();
        if ($parent !== false && $this->extensionMethodExists($parent, '__call')) {
            return $this->extensionCallMethod($parent, '__call', [$name, $params]);
        }

        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()',
            get_class($this),
            $name
        ));
    }

    /**
     * Magic method for `__callStatic()`
     * @param  string $name
     * @param  array  $params
     * @return mixed
     */
    public static function extendableCallStatic($name, $params = null)
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
     * Gets the class loader
     *
     * @return ClassLoader|null
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

    /**
     * Gets the parent class using reflection.
     *
     * The parent class must either not be the `Extendable` class, or must not be using the `ExtendableTrait` trait,
     * in order to prevent infinite loops.
     *
     * @return ReflectionClass|false
     */
    protected function extensionGetParentClass(object $instance = null)
    {
        // Shortcut to prevent infinite loops if the class extends Extendable.
        if ($this instanceof Extendable) {
            return false;
        }

        // Find if any parent uses the Extendable trait
        if (!is_null($instance)) {
            $reflector = $instance;
        } else {
            $reflector = new ReflectionClass($this);
        }
        $parent = $reflector->getParentClass();

        // If there's no parent, stop here.
        if ($parent === false) {
            return false;
        }

        while (!in_array(ExtendableTrait::class, $parent->getTraitNames())) {
            $parent = $parent->getParentClass();
            if ($parent === false) {
                break;
            }
        }

        // If no parent uses the Extendable trait, then return the parent class
        if ($parent === false) {
            return $reflector->getParentClass();
        }

        // Otherwise, we need to loop through until we find the parent class that doesn't use the Extendable trait
        return $this->extensionGetParentClass($parent);
    }

    /**
     * Determines if the given class reflection contains the given method.
     */
    protected function extensionMethodExists(ReflectionClass $class, string $methodName): bool
    {
        try {
            $method = $class->getMethod($methodName);

            if (!$method->isPublic()) {
                return false;
            }
        } catch (ReflectionException $e) {
            return false;
        }

        return true;
    }

    /**
     * Calls a method through reflection.
     */
    protected function extensionCallMethod(ReflectionClass $class, string $method, array $params)
    {
        $method = $class->getMethod($method);
        return $method->invokeArgs($this, $params);
    }
}
