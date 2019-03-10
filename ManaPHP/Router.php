<?php

namespace ManaPHP;

use ManaPHP\Router\Route;
use ManaPHP\Utility\Text;

class _RouterContext
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
 * Class ManaPHP\Router
 *
 * @package router
 *
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Router extends Component implements RouterInterface
{
    /**
     * @var string
     */
    protected $_prefix = '';

    /**
     * @var array
     */
    protected $_areas = [];

    /**
     * @var \ManaPHP\Router\RouteInterface
     */
    protected $_default_route;

    /**
     * @var \ManaPHP\Router\RouteInterface[][]
     */
    protected $_simple_routes = [];

    /**
     * @var \ManaPHP\Router\RouteInterface[]
     */
    protected $_regex_routes = [];

    /**
     * Group constructor.
     *
     * @param bool $useDefaultRoutes
     */
    public function __construct($useDefaultRoutes = true)
    {
        $this->_context = new _RouterContext();

        if ($useDefaultRoutes) {
            $this->_default_route = new Route('/(?:{controller}(?:/{action}(?:/{params})?)?)?');
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
     * @param array $areas
     *
     * @return static
     */
    public function setAreas($areas = null)
    {
        if ($areas === null) {
            $appDir = $this->alias->resolveNS('@app');
            $dirs = glob("$appDir/Areas/*", GLOB_ONLYDIR) ?: glob("$appDir/Controllers/*", GLOB_ONLYDIR);

            $areas = [];
            foreach ($dirs as $dir) {
                $dir = substr($dir, strrpos($dir, '/') + 1);
                if (preg_match('#^[A-Za-z]\w+$#', $dir)) {
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
     * @return \ManaPHP\Router\RouteInterface
     */
    protected function _addRoute($pattern, $paths = null, $method = null)
    {
        $route = new Route($pattern, $paths, $method);
        if ($method !== 'REST' && strpos($pattern, '{') === false) {
            $this->_simple_routes[$method][$pattern] = $route;
        } else {
            $this->_regex_routes[] = $route;
        }

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
     * @param string|array $method
     *
     * @return \ManaPHP\Router\RouteInterface
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
    public function addOptions($pattern = '{all:.*}', $paths = ['controller' => 'cors'])
    {
        return $this->_addRoute($pattern ?: '{all:.*}', $paths, 'OPTIONS');
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
     * Get rewrite info.
     *
     * @return string
     */
    public function getRewriteUri()
    {
        return rtrim($this->request->getGet('_url'), '/') ?: '/';
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
        $context = $this->_context;

        $uri = $uri ?: $this->getRewriteUri();

        if ($method === null) {
            $method = $this->request->getServer('REQUEST_METHOD');
        }

        $context->controller = null;
        $context->action = null;
        $context->params = [];

        $context->matched = false;

        $this->eventsManager->fireEvent('router:beforeRoute', $this);

        if ($this->_prefix) {
            if (strpos($uri, $this->_prefix) === 0) {
                if (($handledUri = substr($uri, strlen($this->_prefix))) === '') {
                    $handledUri = '/';
                }
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
                            $area = Text::camelize(substr($handledUri, 1));
                        } else {
                            $area = Text::camelize(substr($handledUri, 1, $pos - 1));
                        }

                        if (!in_array($area, $this->_areas, true)) {
                            $area = null;
                        }
                    }
                    break;
                }
            }

            if ($parts === false) {
                if ($handledUri !== '/' && $this->_areas) {
                    if (($pos = strpos($handledUri, '/', 1)) !== false) {
                        $area = Text::camelize(substr($handledUri, 1, $pos - 1));
                        if (in_array($area, $this->_areas, true)) {
                            $handledUri = substr($handledUri, $pos);
                        } else {
                            $area = null;
                        }
                    } else {
                        $area = Text::camelize(substr($handledUri, 1));
                        if (in_array($area, $this->_areas, true)) {
                            $handledUri = '/';
                        } else {
                            $area = null;
                        }
                    }
                }

                $handledUri = $handledUri === '/' ? '/' : rtrim($handledUri, '/');
                $parts = $this->_default_route->match($handledUri, $method);
            }
        }

        if ($parts === false) {
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
        $context->params = isset($parts['params']) ? $parts['params'] : [];

        $this->eventsManager->fireEvent('router:afterRoute', $this);

        return $context->matched;
    }

    public function getArea()
    {
        return $this->_context->area;
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
     * Returns the processed action name
     *
     * @return string
     */
    public function getAction()
    {
        return $this->_context->action;
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
     * Checks if the router matches any of the defined routes
     *
     * @return bool
     */
    public function wasMatched()
    {
        return $this->_context->matched;
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
        } elseif (strpos($path, '/') === false) {
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
            $url = $scheme . '://' . $this->request->getServer('HTTP_HOST') . $url;
        }

        return $url;
    }
}