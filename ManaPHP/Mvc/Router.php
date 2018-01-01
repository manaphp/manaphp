<?php

namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\Mvc\Router\Route;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Mvc\Router
 *
 * @package router
 *
 * @property \ManaPHP\Http\RequestInterface $request
 */
class Router extends Component implements RouterInterface
{
    /**
     * @var string
     */
    protected $_module;

    /**
     * @var string
     */
    protected $_controller;

    /**
     * @var string
     */
    protected $_action;

    /**
     * @var array
     */
    protected $_params = [];

    /**
     * @var bool
     */
    protected $_wasMatched = false;

    /**
     * @var \ManaPHP\Mvc\Router\RouteInterface[]
     */
    protected $_routes = [];

    /**
     * @var string
     */
    protected $_prefix = '/';

    /**
     * Group constructor.
     *
     * @param bool $useDefaultRoutes
     */
    public function __construct($useDefaultRoutes = true)
    {
        if ($useDefaultRoutes) {
            $this->add('/:controller/:action/:params');
        }
    }

    /**
     * @param string $prefix
     *
     * @return static
     */
    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->_prefix;
    }

    /**
     * Adds a route applying the common attributes
     *
     * @param string       $pattern
     * @param string|array $paths
     * @param string       $httpMethod
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
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
     */
    public function addRest($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'REST');
    }

    /**
     * Get rewrite info. This info is read from $_GET['_url'] or _SERVER["REQUEST_URI"].
     *
     * @param string $uri
     *
     * @return string
     */
    public function getRewriteUri($uri = null)
    {
        if ($uri === null) {
            if (isset($_GET['_url'])) {
                $uri = $_GET['_url'];
            } elseif (isset($_SERVER['PATH_INFO'])) {
                $uri = $_SERVER['PATH_INFO'];
            } else {
                return '/';
            }
        }

        if ($uri === '/') {
            return '/';
        } else {
            $uri = rtrim($uri, '/');

            return $uri === '' ? '/' : $uri;
        }
    }

    /**
     * Handles routing information received from the rewrite engine
     *
     *<code>
     * //Read the info from the rewrite engine
     * $router->handle();
     *
     * //Manually passing an URL
     * $router->handle('/posts/edit/1');
     *</code>
     *
     * @param string $uri
     * @param string $method
     * @param string $host
     *
     * @return bool
     */
    public function handle($uri = null, $method = null, $host = null)
    {
        $uri = $this->getRewriteUri($uri);

        if ($method === null) {
            $method = $_SERVER['REQUEST_METHOD'];
        }

        if ($host === null && isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        }

        $this->_module = null;
        $this->_controller = null;
        $this->_action = null;
        $this->_params = [];

        $this->_wasMatched = false;

        $this->fireEvent('router:beforeRoute');

        $prefix = $this->_prefix;
        if ($prefix[0] !== '/' && strpos($prefix, $host) === 0) {
            $prefix = $prefix === $host ? '/' : substr($prefix, strlen($host));
        }

        if (strpos($uri, $prefix) === 0) {
            $handledUri = $prefix === '/' ? $uri : substr($uri, strlen($prefix));
            $parts = $this->matchRoute($handledUri, $method);
            if ($parts !== false) {
                $this->_wasMatched = true;
                $this->_module = $parts['module'];
                $this->_controller = $parts['controller'];
                $this->_action = $parts['action'];
                $this->_params = $parts['params'];
            }
        }

        $this->fireEvent('router:afterRoute');

        return $this->_wasMatched;
    }

    /**
     * Returns the processed module name
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->_module;
    }

    /**
     * Returns the processed controller name
     *
     * @return string
     */
    public function getControllerName()
    {
        return $this->_controller;
    }

    /**
     * Returns the processed action name
     *
     * @return string
     */
    public function getActionName()
    {
        return $this->_action;
    }

    /**
     * Returns the processed parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Checks if the router matches any of the defined routes
     *
     * @return bool
     */
    public function wasMatched()
    {
        return $this->_wasMatched;
    }

    /**
     * @param array|string $args
     * @param bool         $absolute
     *
     * @return string
     */
    public function createActionUrl($args, $absolute = false)
    {
        if (is_string($args)) {
            if (($pos = strpos($args, '?')) !== false) {
                $path = substr($args, 0, $pos);
                parse_str(substr($args, $pos + 1), $params);
            } else {
                $path = $args;
                $params = [];
            }
        } else {
            $path = $args[0];
            unset($args[0]);
            $params = $args;
        }

        if ($path === '') {
            $ca = $this->_controller . '/' . $this->_action;
        } elseif (strpos($path, '/') === false) {
            $ca = $this->_controller . '/' . $path;
        } elseif ($path === '/') {
            $ca = '';
        } elseif ($path[0] === '/') {
            $pos = strpos($path, '/', 1);
            if ($pos === false) {
                $module = substr($path, 1);
                $ca = '';
            } else {
                $module = substr($path, 1, $pos - 1);
                $ca = rtrim(substr($path, $pos + 1), '/');
            }
            $module = Text::underscore($module);
        } else {
            $ca = rtrim($path, '/');
        }

        if (($pos = strrpos($ca, '/index')) !== false && $pos + 6 === strlen($ca)) {
            $ca = substr($ca, 0, -6);
        }

        if ($ca === 'index' || $ca === 'index/') {
            $ca = '';
        }

        if (!isset($module)) {
            $module = Text::underscore($this->_module);
        }

        $url = $this->alias->get('@web') . '/' . ($module ? $module . '/' : '') . lcfirst($ca);
        if ($url !== '/') {
            $url = rtrim($url, '/');
        }

        if ($params !== []) {
            if (isset($params['#'])) {
                $fragment = $params['#'];
                unset($params['#']);
            }

            /** @noinspection NotOptimalIfConditionsInspection */
            if ($params !== []) {
                $url .= '?' . http_build_query($params);
            }
            if (isset($fragment)) {
                $url .= '#' . $fragment;
            }
        }

        if ($absolute) {
            $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $url;
        }

        return $url;
    }

    /**
     * @param string $uri
     * @param string $method
     *
     * @return array|false
     */
    public function matchRoute($uri, $method = 'GET')
    {
        $uri = rtrim($uri, '/') ?: '/';

        for ($i = count($this->_routes) - 1; $i >= 0; $i--) {
            $route = $this->_routes[$i];
            $parts = $route->match($uri, $method);
            if ($parts !== false) {
                $module = isset($parts['module']) ? ($parts['module'] ?: 'index') : '';
                $controller = isset($parts['controller']) && $parts['controller'] !== '' ? $parts['controller'] : 'index';
                $action = isset($parts['action']) && $parts['action'] !== '' ? $parts['action'] : 'index';
                $params = isset($parts['params']) ? trim($parts['params'], '/') : '';

                unset($parts['controller'], $parts['action'], $parts['params']);
                if ($params !== '') {
                    $parts = array_merge($parts, explode('/', $params));
                }
                return ['module' => $module, 'controller' => $controller, 'action' => $action, 'params' => $parts];
            }
        }

        return false;
    }
}