<?php

namespace ManaPHP\Mvc\Router;

use ManaPHP\Component;

/**
 * Class ManaPHP\Mvc\Router\Group
 *
 * @package router
 */
class Group extends Component implements GroupInterface
{
    /**
     * @var \ManaPHP\Mvc\Router\RouteInterface[]
     */
    protected $_routes = [];

    /**
     * Group constructor.
     *
     * @param bool $useDefaultRoutes
     *
     * @throws \ManaPHP\Mvc\Router\Route\Exception
     */
    public function __construct($useDefaultRoutes = true)
    {
        if ($useDefaultRoutes) {
            $this->add('/(:controller)?(/:action)?(/:params)?');
        }
    }

    /**
     * Adds a route applying the common attributes
     *
     * @param string       $pattern
     * @param string|array $paths
     * @param string       $httpMethod
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Route\Exception
     */
    protected function _addRoute($pattern, $paths = null, $httpMethod = null)
    {
        $route = new Route($pattern, $paths, $httpMethod);
        $this->_routes[] = $route;

        return $route;
    }

    /**
     * Adds a route to the router on any HTTP method
     *
     *<code>
     * $router->add('/about', 'About::index');
     *</code>
     *
     * @param string       $pattern
     * @param string|array $paths
     * @param string|array $httpMethod
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Route\Exception
     */
    public function add($pattern, $paths = null, $httpMethod = null)
    {
        return $this->_addRoute($pattern, $paths, $httpMethod);
    }

    /**
     * Adds a route to the router that only match if the HTTP method is GET
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Route\Exception
     */
    public function addGet($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'GET');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is POST
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Route\Exception
     */
    public function addPost($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'POST');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is PUT
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Route\Exception
     */
    public function addPut($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'PUT');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is PATCH
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Route\Exception
     */
    public function addPatch($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'PATCH');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is DELETE
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Route\Exception
     */
    public function addDelete($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'DELETE');
    }

    /**
     * Add a route to the router that only match if the HTTP method is OPTIONS
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Route\Exception
     */
    public function addOptions($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'OPTIONS');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is HEAD
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Route\Exception
     */
    public function addHead($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'HEAD');
    }

    /**
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     * @throws \ManaPHP\Mvc\Router\Route\Exception
     */
    public function addRest($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'REST');
    }

    /**
     * @param string $uri
     * @param string $method
     *
     * @return array|false
     * @throws \ManaPHP\Mvc\Router\Exception
     */
    public function match($uri, $method = 'GET')
    {
        $uri = rtrim($uri, '/') ?: '/';

        for ($i = count($this->_routes) - 1; $i >= 0; $i--) {
            $route = $this->_routes[$i];
            $parts = $route->match($uri, $method);
            if ($parts !== false) {
                $controller = isset($parts['controller']) ? $parts['controller'] : 'index';
                $action = isset($parts['action']) ? $parts['action'] : 'index';
                $params = isset($parts['params']) ? trim($parts['params'], '/') : '';

                unset($parts['controller'], $parts['action'], $parts['params']);
                if ($params !== '') {
                    $parts = array_merge($parts, explode('/', $params));
                }
                return ['controller' => $controller, 'action' => $action, 'params' => $parts];
            }
        }

        return false;
    }
}