<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Str;
use ManaPHP\Http\Router\NotFoundRouteException;
use ManaPHP\Http\Router\Route;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class RouterContext
{
    /**
     * @var string
     */
    public $area;

    /**
     * @var string
     */
    public $controller;

    /**
     * @var string
     */
    public $action;

    /**
     * @var array
     */
    public $params = [];

    /**
     * @var bool
     */
    public $matched = false;
}

/**
 * @property-read \ManaPHP\Http\RequestInterface    $request
 * @property-read \ManaPHP\Http\DispatcherInterface $dispatcher
 * @property-read \ManaPHP\Http\RouterContext       $_context
 */
class Router extends Component implements RouterInterface
{
    /**
     * @var bool
     */
    protected $_case_sensitive = true;

    /**
     * @var string
     */
    protected $_prefix = '';

    /**
     * @var array
     */
    protected $_areas = [];

    /**
     * @var \ManaPHP\Http\Router\RouteInterface[]
     */
    protected $_default_routes = [];

    /**
     * @var \ManaPHP\Http\Router\RouteInterface[][]
     */
    protected $_simple_routes = [];

    /**
     * @var \ManaPHP\Http\Router\RouteInterface[]
     */
    protected $_regex_routes = [];

    /**
     * @param bool $useDefaultRoutes
     */
    public function __construct($useDefaultRoutes = true)
    {
        if ($useDefaultRoutes) {
            $this->_default_routes = [
                new Route('/(?:{controller}(?:/{action:\d[-\w]*$|[a-zA-Z]\w*}(?:/{params})?)?)?')
            ];
        }
    }

    /**
     * @return bool
     */
    public function isCaseSensitive()
    {
        return $this->_case_sensitive;
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
    public function setAreas($areas = null)
    {
        if ($areas === null) {
            $areas = [];
            foreach (glob($this->alias->resolve('@app/Areas/*'), GLOB_ONLYDIR) as $dir) {
                $dir = substr($dir, strrpos($dir, '/') + 1);
                if (preg_match('#^[A-Z]\w+$#', $dir)) {
                    $areas[] = $dir;
                }
            }
        }

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
     * @param string       $method
     *
     * @return \ManaPHP\Http\Router\RouteInterface
     */
    protected function _addRoute($pattern, $paths = null, $method = null)
    {
        $route = new Route($pattern, $paths, $method, $this->_case_sensitive);
        if (strpbrk($pattern, ':{') === false) {
            $this->_simple_routes[$method][$pattern] = $route;
        } else {
            $this->_regex_routes[] = $route;
        }

        return $route;
    }

    /**
     * Adds a route to the router on any HTTP method
     *
     * @param string       $pattern
     * @param string|array $paths
     * @param string|array $method
     *
     * @return \ManaPHP\Http\Router\RouteInterface
     */
    public function add($pattern, $paths = null, $method = null)
    {
        return $this->_addRoute($pattern, $paths, $method);
    }

    /**
     * Adds a route to the router that only match if the HTTP method is GET
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Http\Router\RouteInterface
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
     * @return \ManaPHP\Http\Router\RouteInterface
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
     * @return \ManaPHP\Http\Router\RouteInterface
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
     * @return \ManaPHP\Http\Router\RouteInterface
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
     * @return \ManaPHP\Http\Router\RouteInterface
     */
    public function addDelete($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'DELETE');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is HEAD
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Http\Router\RouteInterface
     */
    public function addHead($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'HEAD');
    }

    /**
     * @param string $pattern
     * @param string $controller
     *
     * @return \ManaPHP\Http\Router\RouteInterface
     */
    public function addRest($pattern, $controller = null)
    {
        $pattern .= '(/{params:[-\w]+})?';

        if ($controller === null) {
            if (str_contains($pattern, '/:controller')) {
                return $this->_addRoute($pattern, null, 'REST');
            }

            if (!preg_match('#/(\w+)$#', $pattern, $match)) {
                throw new MisuseException('must provide paths');
            }
            $controller = Str::singular($match[1]);
        }

        return $this->_addRoute($pattern, $controller, 'REST');
    }

    /**
     * Get rewrite info.
     *
     * @return string
     */
    public function getRewriteUri()
    {
        if (($url = $this->request->get('_url', '')) === '') {
            $request_uri = $this->request->getServer('REQUEST_URI', '/');
            $pos = strpos($request_uri, '?');
            $url = $pos === false ? $request_uri : substr($request_uri, 0, $pos);
        }

        $url = rtrim($url, '/') ?: '/';
        $web = $this->alias->get('@web') ?? '';
        if ($web === '') {
            return $url;
        } elseif (str_starts_with($url, $web)) {
            $url = substr($url, strlen($web));
            return $url === '' ? '/' : $url;
        } else {
            return $url;
        }
    }

    /**
     * @param string $uri
     * @param string $method
     *
     * @return array|false
     */
    protected function _matchDefaultRoutes($uri, $method)
    {
        $handledUri = $uri;

        $area = null;
        if ($handledUri !== '/' && $this->_areas) {
            if (($pos = strpos($handledUri, '/', 1)) !== false) {
                $area = Str::camelize(substr($handledUri, 1, $pos - 1));
                if (in_array($area, $this->_areas, true)) {
                    $handledUri = substr($handledUri, $pos);
                } else {
                    $area = null;
                }
            } else {
                $area = Str::camelize(substr($handledUri, 1));
                if (in_array($area, $this->_areas, true)) {
                    $handledUri = '/';
                } else {
                    $area = null;
                }
            }
        }

        $handledUri = $handledUri === '/' ? '/' : rtrim($handledUri, '/');

        for ($i = count($this->_default_routes) - 1; $i >= 0; $i--) {
            $route = $this->_default_routes[$i];
            if (($parts = $route->match($handledUri, $method)) !== false) {
                if ($area !== null) {
                    $parts['area'] = $area;
                }
                return $parts;
            }
        }

        return false;
    }

    /**
     * Handles routing information received from the rewrite engine
     *
     * @param string $uri
     * @param string $method
     *
     * @return \ManaPHP\Http\RouterContext|false
     */
    public function match($uri = null, $method = null)
    {
        $context = $this->_context;

        $this->fireEvent('request:routing');

        $uri = $uri ?: $this->getRewriteUri();

        if ($method === null) {
            $method = $this->request->getMethod();
        }

        $context->controller = null;
        $context->action = null;
        $context->params = [];

        $context->matched = false;

        if ($this->_prefix) {
            if (str_starts_with($uri, $this->_prefix)) {
                $handledUri = substr($uri, strlen($this->_prefix)) ?: '/';
            } else {
                $handledUri = false;
            }
        } else {
            $handledUri = $uri;
        }

        $area = null;
        $routes = $this->_simple_routes;
        if ($handledUri === false) {
            $parts = false;
        } elseif (isset($routes[$method][$handledUri])) {
            $parts = $routes[$method][$handledUri]->match($handledUri, $method);
        } elseif (isset($routes[''][$handledUri])) {
            $parts = $routes[''][$handledUri]->match($handledUri, $method);
        } else {
            $parts = false;
            $routes = $this->_regex_routes;
            for ($i = count($routes) - 1; $i >= 0; $i--) {
                $route = $routes[$i];
                if (($parts = $route->match($handledUri, $method)) !== false) {
                    if ($handledUri !== '/' && $this->_areas) {
                        if (($pos = strpos($handledUri, '/', 1)) === false) {
                            $area = Str::camelize(substr($handledUri, 1));
                        } else {
                            $area = Str::camelize(substr($handledUri, 1, $pos - 1));
                        }

                        if (!in_array($area, $this->_areas, true)) {
                            $area = null;
                        }
                    }
                    break;
                }
            }

            if ($parts === false) {
                $parts = $this->_matchDefaultRoutes($handledUri, $method);
            }
        }

        if ($parts === false) {
            $this->fireEvent('request:routed');

            return false;
        }

        $context->matched = true;

        if ($area) {
            $context->area = $area;
        } elseif (isset($parts['area'])) {
            $context->area = $parts['area'];
        }

        $context->controller = $parts['controller'];
        $context->action = $parts['action'];
        $context->params = $parts['params'] ?? [];

        $this->fireEvent('request:routed');

        return $context;
    }

    /**
     * Handles routing information received from the rewrite engine
     *
     * @param string $uri
     * @param string $method
     *
     * @return mixed
     */
    public function dispatch($uri = null, $method = null)
    {
        if (!$router_context = $this->match($uri, $method)) {
            throw new NotFoundRouteException(['router does not have matched route for `%s`', $this->getRewriteUri()]);
        }

        return $this->dispatcher->dispatch($router_context);
    }

    /**
     * @return string
     */
    public function getArea()
    {
        return $this->_context->area;
    }

    /**
     * @param string $area
     *
     * @return static
     */
    public function setArea($area)
    {
        $this->_context->area = $area;

        return $this;
    }

    /**
     * Returns the processed controller name
     *
     * @return string
     */
    public function getController()
    {
        return $this->_context->controller;
    }

    /**
     * @param string $controller
     *
     * @return static
     */
    public function setController($controller)
    {
        $this->_context->controller = $controller;

        return $this;
    }

    /**
     * Returns the processed action name
     *
     * @return string
     */
    public function getAction()
    {
        return $this->_context->action;
    }

    /**
     * @param string $action
     *
     * @return static
     */
    public function setAction($action)
    {
        $this->_context->action = $action;

        return $this;
    }

    /**
     * Returns the processed parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_context->params;
    }

    /**
     * @param array $params
     *
     * @return static
     */
    public function setParams($params)
    {
        $this->_context->params = $params;

        return $this;
    }

    /**
     * Checks if the router matches any of the defined routes
     *
     * @return bool
     */
    public function wasMatched()
    {
        return $this->_context->matched;
    }

    /**
     * @param bool $matched
     *
     * @return void
     */
    public function setMatched($matched)
    {
        $this->_context->matched = $matched;
    }

    /**
     * @param array|string $args
     * @param bool|string  $scheme
     *
     * @return string
     */
    public function createUrl($args, $scheme = false)
    {
        $context = $this->_context;

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

        $area = $context->area;
        $controller = $context->controller;
        if ($path === '') {
            $action = $context->action;
            $ca = $area ? "{$area}/{$controller}/$action" : "{$controller}/$action";
        } elseif (!str_contains($path, '/')) {
            $ca = $area ? "{$area}/{$controller}/$path" : "{$controller}/$path";
        } elseif ($path === '/') {
            $ca = '';
        } elseif ($path[0] === '/') {
            $ca = substr($path, 1);
        } elseif ($area) {
            $ca = $area . '/' . $path;
        } else {
            $ca = rtrim($path, '/');
        }

        while (($pos = strrpos($ca, '/index')) !== false && $pos + 6 === strlen($ca)) {
            $ca = substr($ca, 0, $pos);
        }

        $url = $this->alias->get('@web') . $this->_prefix . '/' . lcfirst($ca);
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

        if ($scheme) {
            if ($scheme === true) {
                $scheme = $this->request->getScheme();
            }
            return ($scheme === '//' ? '//' : "$scheme://") . $this->request->getServer('HTTP_HOST') . $url;
        } else {
            return $url;
        }
    }
}
