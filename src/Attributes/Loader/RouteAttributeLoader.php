<?php


namespace Meast\Router\Attributes\Loader;


use Meast\Router\Attributes\Route;
use ReflectionClass;
use ReflectionMethod;

class RouteAttributeLoader
{
    public function __construct (
        private ReflectionClass $class
    )
    {
    }

    /**
     * @return ReflectionMethod[]
     */
    public function getMethodsWithRouteAttribute (): array
    {
        $methods = [];
        foreach ($this->class->getMethods() as $method) {
            if ($method->getAttributes(Route::class)) {
                $methods[] = $method;
            }
        }

        return $methods;
    }

    public function getClass (): ReflectionClass
    {
        return $this->class;
    }
}