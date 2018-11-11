<?php

namespace ManaPHP;

use ManaPHP\Router\Route;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Router
 *
 * @package router
 *
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Router extends Component implements RouterInterface
{
    /**
     * @var array
     */
    protected $_areas = [];

    /**
     * @var string
     */
    protected $_area;

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
     * @var \ManaPHP\Router\RouteInterface[]
     */
    protected $_routes = [];

    /**
     * @var string
     */
    protected $_prefix = '';

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

    public function saveInstanceState()
    {
        return true;
    }

    public function restoreInstanceState($data)
    {
        $this->_area = null;
        $this->_controller = null;
        $this->_action = null;
        $this->_params = [];
        $this->_wasMatched = false;
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
     * @param array $areas
     *
     * @return static
     */
    public function setAreas($areas)
    {
        $this->_areas = $areas;

        return $this;
    }

    /**
     * @return array
     */
    public function getAreas()
    {
        return $this->_areas;
    }

    /**
     * Adds a route applying the common attributes
     *
     * @param string       $pattern
     * @param string|array $paths
     * @param string       $httpMethod
     *
     * @return \ManaPHP\Router\RouteInterface
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
     * @return \ManaPHP\Router\RouteInterface
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
     * @return \ManaPHP\Router\RouteInterface
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
     * @return \ManaPHP\Router\RouteInterface
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
     * @return \ManaPHP\Router\RouteInterface
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
     * @return \ManaPHP\Router\RouteInterface
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
     * @return \ManaPHP\Router\RouteInterface
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
     * @return \ManaPHP\Router\RouteInterface
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
     * @return \ManaPHP\Router\RouteInterface
     */
    public function addHead($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'HEAD');
    }

    /**
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Router\RouteInterface
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
     * @param string $uri
     * @param string $method
     *
     * @return bool
     */
    public function match($uri = null, $method = null)
    {
        $uri = $this->getRewriteUri($uri);

        if ($method === null) {
            $method = $_SERVER['REQUEST_METHOD'];
        }

        $this->_controller = null;
        $this->_action = null;
        $this->_params = [];

        $this->_wasMatched = false;

        $this->fireEvent('router:beforeRoute');

        $area = null;
        $handledUri = $this->_prefix ? substr($uri, strlen($this->_prefix)) : $uri;
        if ($handledUri !== '/' && $this->_areas) {
            if (substr_count($handledUri, '/') < 2) {
                $handledUri .= '/';
            }

            $pos = strpos($handledUri, '/', 1);
            $area = Text::camelize(substr($handledUri, 1, $pos - 1));
            if (in_array($area, $this->_areas, true)) {
                $handledUri = substr($handledUri, $pos);
            } else {
                $area = null;
            }
        }

        $handledUri = rtrim($handledUri, '/') ?: '/';

        $parts = false;
        for ($i = count($this->_routes) - 1; $i >= 0; $i--) {
            $route = $this->_routes[$i];
            $parts = $route->match($handledUri, $method);
            if ($parts !== false) {
                break;
            }
        }

        if ($parts === false) {
            return false;
        }

        $this->_wasMatched = true;

        if ($area) {
            $this->_area = $area;
        } elseif (isset($parts['area'])) {
            $this->_area = $parts['area'];
        }

        $this->_controller = $parts['controller'];
        $this->_action = $parts['action'];
        $this->_params = $parts['params'];

        $this->fireEvent('router:afterRoute');

        return $this->_wasMatched;
    }

    public function getArea()
    {
        return $this->_area;
    }

    /**
     * Returns the processed controller name
     *
     * @return string
     */
    public function getController()
    {
        return $this->_controller;
    }

    /**
     * Returns the processed action name
     *
     * @return string
     */
    public function getAction()
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
     * @param bool|string  $scheme
     *
     * @return string
     */
    public function createUrl($args, $scheme = false)
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
            $ca = $this->_area ? "{$this->_area}/{$this->_controller}/$this->_action" : "{$this->_controller}/$this->_action";
        } elseif (strpos($path, '/') === false) {
            $ca = $this->_area ? "{$this->_area}/{$this->_controller}/$path" : "{$this->_controller}/$path";
        } elseif ($path === '/') {
            $ca = '';
        } elseif ($path[0] === '/') {
            $ca = substr($path, 1);
        } elseif ($this->_area) {
            $ca = $this->_area . '/' . $path;
        } else {
            $ca = rtrim($path, '/');
        }

        while (($pos = strrpos($ca, '/index')) !== false && $pos + 6 === strlen($ca)) {
            $ca = substr($ca, 0, $pos);
        }

        $url = $this->alias->resolve('@web') . $this->_prefix . '/' . lcfirst($ca);
        if ($url !== '/') {
            $url = rtrim($url, '/');
        }

        if ($params !== []) {
            $fragment = null;
            if (isset($params['#'])) {
                $fragment = $params['#'];
                unset($params['#']);
            }

            /** @noinspection NotOptimalIfConditionsInspection */
            if ($params !== []) {
                $url .= '?' . http_build_query($params);
            }
            if ($fragment !== null) {
                $url .= '#' . $fragment;
            }
        }

        if ($scheme === true) {
            $scheme = $this->request->getScheme();
        }

        if ($scheme) {
            $url = $scheme . '://' . $_SERVER['HTTP_HOST'] . $url;
        }

        return $url;
    }
}