<?php namespace Winter\Storm\Foundation;

use Closure;
use Winter\Storm\Foundation\Application;
use Illuminate\Contracts\Container\BindingResolutionException;
use ReflectionClass;
use ReflectionParameter;
use ReflectionNamedType;
use ReflectionUnionType;

class Maker
{
    /**
     * @var array
     */
    protected $bindings = [];

    /**
     * @var Application
     */
    protected $app;

    /**
     * Maker constructor.
     *
     * @param Application $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function make($abstract, array $parameters = [])
    {
        return $this->build(
            $this->getBinding($abstract),
            $parameters
        );
    }

    public function bind($abstract, Closure $concrete)
    {
        $this->bindings[$abstract] = $concrete;
    }

    protected function build($concrete, $parameters)
    {
        if ($concrete instanceof Closure) {
            return $concrete($this->app, $parameters);
        }

        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new BindingResolutionException("Target [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        $parameters = $this->keyParametersByArgument(
            $dependencies,
            $parameters
        );

        $instances = $this->getDependencies(
            $dependencies,
            $parameters
        );

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * If extra parameters are passed by numeric ID, rekey them by argument name.
     *
     * @param  array $dependencies
     * @param  array $parameters
     *
     * @return array
     */
    protected function keyParametersByArgument(array $dependencies, array $parameters)
    {
        foreach ($parameters as $key => $value) {
            if (is_numeric($key)) {
                unset($parameters[$key]);
                $parameters[$dependencies[$key]->name] = $value;
            }
        }

        return $parameters;
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * @param  array $parameters
     * @param  array $primitives
     *
     * @return array
     */
    protected function getDependencies(array $parameters, array $primitives = [])
    {
        $dependencies = [];

        /** @var ReflectionParameter $parameter */
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getType();

            if (array_key_exists($parameter->name, $primitives)) {
                $dependencies[] = $primitives[$parameter->name];
            } elseif (is_null($dependency)) {
                $dependencies[] = $this->resolvePrimitive($parameter);
            } elseif ($dependency instanceof ReflectionUnionType === false) {
                $dependencies[] = $this->resolveClass($parameter);
            }
        }

        return $dependencies;
    }

    /**
     * @param ReflectionParameter $parameter
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        /** @var ReflectionNamedType */
        $type = $parameter->getType();

        try {
            return $this->getFromContainer($type->getName());
        } catch (BindingResolutionException $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }
            throw $e;
        }
    }

    protected function getBinding($abstract)
    {
        return $this->isBound($abstract) ? $this->bindings[$abstract] : $abstract;
    }

    /**
     * @return bool
     */
    protected function isBound($abstract)
    {
        return array_key_exists($abstract, $this->bindings);
    }

    /**
     * @param ReflectionParameter $parameter
     *
     * @return mixed|void
     */
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        return $this->unresolvablePrimitive($parameter);
    }

    /**
     * @param ReflectionParameter $parameter
     *
     * @throws BindingResolutionException
     */
    protected function unresolvablePrimitive(ReflectionParameter $parameter)
    {
        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";
        throw new BindingResolutionException($message);
    }

    /**
     * @param string $abstract
     *
     * @return mixed
     */
    protected function getFromContainer($abstract)
    {
        return $this->app->make($abstract);
    }
}
