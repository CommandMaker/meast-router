<?php

namespace Meast\Router;

use Exception;
use HaydenPierce\ClassFinder\ClassFinder;
use Meast\Router\Attributes\Loader\RouteAttributeLoader;
use Meast\Router\Exception\RouteNotFoundException;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use function is_string;
use function strtolower;

class Router
{
    /**
     * @var Request $request The request
     */
    private Request $request;

    /**
     * @var Route[] $routes
     */
    private array $routes = [];

    /**
     * @var string $url
     */
    private string $url;

    private ?array $attributesPath;

    /**
     * Router constructor.
     * @param Request $request
     * @param string $base If the project is in a subdirectory, you need to specify the subdirectory(ies) name(s) here
     * @param string[]|ReflectionMethod[]|null $attributesPath
     */
    public function __construct (Request $request, string $base = "", array $attributesPath = null)
    {
        $this->request = $request;
        $path = preg_replace("#\.#", "\.", $base);
        $this->url = trim(preg_replace("#$path#", "", $request->getRequestUri()), "/");
        $this->attributesPath = $attributesPath;
    }

    /**
     * @param string $path Path of the url ("/" for root page)
     * @param callable|string $callable The function to call when the route match
     * @return Route
     */
    public function get (string $path, callable|string $callable): Route
    {
        $route = new Route($this->request, trim($path, "/"), $callable);
        $this->routes["GET"][] = $route;
        return $route;
    }

    /**
     * Register all function with the Route attribute
     *
     * @throws Exception
     * @see \Meast\Router\Attributes\Route
     *
     */
    public function registerAttributes (): void
    {
        if (!$this->attributesPath) {
            throw new RuntimeException("The attribute path cannot be null");
        }

        foreach ($this->attributesPath as $path) {
            if (is_string($path)) {
                $k = ClassFinder::getClassesInNamespace($path);

                if ($k) {
                    foreach ($k as $klass) {
                        $this->registerAttributesRoutes($klass);
                    }
                } else {
                    $this->registerAttributesRoutes($path);
                }
            }
        }
    }

    /**
     * @throws RouteNotFoundException
     * @throws Exception
     */
    public function run ()
    {
        if ($this->attributesPath) {
            $this->registerAttributes();
        }

        /** @var Route $route */
        foreach ($this->routes[$this->request->getMethod()] as $route) {
            if ($route->match($this->url)) {
                return $route->call();
            }
        }

        throw new RouteNotFoundException("No route found for $this->url");
    }

    private function registerAttributesRoutes (string $klass): void
    {
        $ral = new RouteAttributeLoader(new ReflectionClass($klass));
        $mwa = $ral->getMethodsWithRouteAttribute();

        foreach ($mwa as $method) {
            /** @var \Meast\Router\Attributes\Route $attr */
            $attr = $method->getAttributes(\Meast\Router\Attributes\Route::class)[0]->newInstance();
            $httpMethod = strtolower($attr->getMethod());
            $className = $method->getDeclaringClass()->getName();
            $methodName = $method->getName();

            $this->$httpMethod($attr->getPath(), "$className@$methodName");
        }
    }
}
