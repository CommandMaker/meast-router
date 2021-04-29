<?php


namespace Meast\Router;


use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use function array_shift;
use function array_unshift;
use function call_user_func_array;
use function dd;
use function explode;
use function is_string;

class Route
{
    /**
     * @var Request $request The request
     */
    private Request $request;

    /**
     * @var callable|string $callable
     */
    private $callable;

    /**
     * @var string $name Name of the route
     */
    private string $name;

    /**
     * @var string $path Path of the route
     */
    private string $path;

    /**
     * @var array $params
     */
    private array $params;

    /**
     * Route constructor.
     * @param Request $request
     * @param string $path Path of the route
     * @param callable|string $callable |string $callable
     */
    public function __construct(Request $request, string $path, callable|string $callable)
    {
        $this->request = $request;
        $this->callable = $callable;
        $this->path = $path;
    }

    public function match (string $url): bool
    {
        $path = preg_replace('#:([\w]+)#', '([^/]+)', $this->path);
        $regex = "#^$path$#i";

        if (!preg_match($regex, $url, $matches)) {
            return false;
        }

        array_shift($matches);
        array_unshift($matches, $this->request);
        $this->params = $matches;
        return true;
    }

    public function call ()
    {
        if (is_string($this->callable)) {
            /*
             * '\App\Controller\HomeController@home'
             */
            $a = explode('@', $this->callable);
            $controller = new $a[0]();
            $method = $a[1];

            return call_user_func_array([$controller, $method], $this->params);
        }

        return call_user_func_array($this->callable, $this->params);
    }

    public function getPath (): string
    {
        return $this->path;
    }
}