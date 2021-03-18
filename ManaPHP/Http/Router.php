<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Str;
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
 * @property-read \ManaPHP\AliasInterface        $alias
 * @property-read \ManaPHP\Http\RequestInterface $request
 * @property-read \ManaPHP\Http\RouterContext    $context
 */
class Router extends Component implements RouterInterface
{
    /**
     * @var bool
     */
    protected $case_sensitive = true;

    /**
     * @var string
     */
    protected $prefix = '';

    /**
     * @var array
     */
    protected $areas = [];

    /**
     * @var \ManaPHP\Http\Router\RouteInterface[]
     */
    protected $defaults = [];

    /**
     * @var \ManaPHP\Http\Router\RouteInterface[][]
     */
    protected $simples = [];

    /**
     * @var \ManaPHP\Http\Router\RouteInterface[]
     */
    protected $regexes = [];

    /**
     * @param bool $useDefaultRoutes
     */
    public function __construct($useDefaultRoutes = true)
    {
        if ($useDefaultRoutes) {
            $this->defaults = [
                new Route('/(?:{controller}(?:/{action:\d[-\w]*$|[a-zA-Z]\w*}(?:/{params})?)?)?')
            ];
        }
    }

    /**
     * @return bool
     */
    public function isCaseSensitive()
    {
        return $this->case_sensitive;
    }

    /**
     * @param string $prefix
     *
     * @return static
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
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

        $this->areas = $areas;

        return $this;
    }

    /**
     * @return array
     */
    public function getAreas()
    {
        return $this->areas;
    }

    /**
     * Adds a route applying the common attributes
     *
     * @param string       $pattern
     * @param string|array $paths
     * @param string|array $methods
     *
     * @return \ManaPHP\Http\Router\RouteInterface
     */
    protected function addRoute($pattern, $paths = null, $methods = null)
    {
        $route = new Route($pattern, $paths, $methods, $this->case_sensitive);
        if (!is_array($methods) && strpbrk($pattern, ':{') === false) {
            $this->simples[$methods][$pattern] = $route;
        } else {
            $this->regexes[] = $route;
        }

        return $route;
    }

    /**
     * Adds a route to the router on any HTTP method
     *
     * @param string       $pattern
     * @param string|array $paths
     * @param string|array $methods
     *
     * @return \ManaPHP\Http\Router\RouteInterface
     */
    public function add($pattern, $paths = null, $methods = null)
    {
        return $this->addRoute($pattern, $paths, $methods);
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
        return $this->addRoute($pattern, $paths, 'GET');
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
        return $this->addRoute($pattern, $paths, 'POST');
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
        return $this->addRoute($pattern, $paths, 'PUT');
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
        return $this->addRoute($pattern, $paths, 'PATCH');
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
        return $this->addRoute($pattern, $paths, 'DELETE');
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
        return $this->addRoute($pattern, $paths, 'HEAD');
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
                return $this->addRoute($pattern, null, 'REST');
            }

            if (!preg_match('#/(\w+)$#', $pattern, $match)) {
                throw new MisuseException('must provide paths');
            }
            $controller = Str::singular($match[1]);
        }

        return $this->addRoute($pattern, $controller, 'REST');
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

        if ($url[0] !== '/') {
            $url = parse_url($url, PHP_URL_PATH);
        }

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
    protected function matchDefaultRoutes($uri, $method)
    {
        $handledUri = $uri;

        $area = null;
        if ($handledUri !== '/' && $this->areas) {
            if (($pos = strpos($handledUri, '/', 1)) !== false) {
                $area = Str::pascalize(substr($handledUri, 1, $pos - 1));
                if (in_array($area, $this->areas, true)) {
                    $handledUri = substr($handledUri, $pos);
                } else {
                    $area = null;
                }
            } else {
                $area = Str::pascalize(substr($handledUri, 1));
                if (in_array($area, $this->areas, true)) {
                    $handledUri = '/';
                } else {
                    $area = null;
                }
            }
        }

        $handledUri = $handledUri === '/' ? '/' : rtrim($handledUri, '/');

        for ($i = count($this->defaults) - 1; $i >= 0; $i--) {
            $route = $this->defaults[$i];
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
     * @return bool
     */
    public function match($uri = null, $method = null)
    {
        $context = $this->context;

        $this->fireEvent('request:routing');

        $uri = $uri ?: $this->getRewriteUri();

        if ($method === null) {
            $method = $this->request->getMethod();
        }

        $context->controller = null;
        $context->action = null;
        $context->params = [];

        $context->matched = false;

        if ($this->prefix) {
            if (str_starts_with($uri, $this->prefix)) {
                $handledUri = substr($uri, strlen($this->prefix)) ?: '/';
            } else {
                $handledUri = false;
            }
        } else {
            $handledUri = $uri;
        }

        $area = null;
        $routes = $this->simples;
        if ($handledUri === false) {
            $parts = false;
        } elseif (isset($routes[$method][$handledUri])) {
            $parts = $routes[$method][$handledUri]->match($handledUri, $method);
        } elseif (isset($routes[''][$handledUri])) {
            $parts = $routes[''][$handledUri]->match($handledUri, $method);
        } else {
            $parts = false;
            $routes = $this->regexes;
            for ($i = count($routes) - 1; $i >= 0; $i--) {
                $route = $routes[$i];
                if (($parts = $route->match($handledUri, $method)) !== false) {
                    if ($handledUri !== '/' && $this->areas) {
                        if (($pos = strpos($handledUri, '/', 1)) === false) {
                            $area = Str::pascalize(substr($handledUri, 1));
                        } else {
                            $area = Str::pascalize(substr($handledUri, 1, $pos - 1));
                        }

                        if (!in_array($area, $this->areas, true)) {
                            $area = null;
                        }
                    }
                    break;
                }
            }

            if ($parts === false) {
                $parts = $this->matchDefaultRoutes($handledUri, $method);
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

        return $context->matched;
    }

    /**
     * @return string
     */
    public function getArea()
    {
        return $this->context->area;
    }

    /**
     * @param string $area
     *
     * @return static
     */
    public function setArea($area)
    {
        $this->context->area = $area;

        return $this;
    }

    /**
     * Returns the processed controller name
     *
     * @return string
     */
    public function getController()
    {
        return $this->context->controller;
    }

    /**
     * @param string $controller
     *
     * @return static
     */
    public function setController($controller)
    {
        $this->context->controller = $controller;

        return $this;
    }

    /**
     * Returns the processed action name
     *
     * @return string
     */
    public function getAction()
    {
        return $this->context->action;
    }

    /**
     * @param string $action
     *
     * @return static
     */
    public function setAction($action)
    {
        $this->context->action = $action;

        return $this;
    }

    /**
     * Returns the processed parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->context->params;
    }

    /**
     * @param array $params
     *
     * @return static
     */
    public function setParams($params)
    {
        $this->context->params = $params;

        return $this;
    }

    /**
     * Checks if the router matches any of the defined routes
     *
     * @return bool
     */
    public function wasMatched()
    {
        return $this->context->matched;
    }

    /**
     * @param bool $matched
     *
     * @return void
     */
    public function setMatched($matched)
    {
        $this->context->matched = $matched;
    }

    /**
     * @param array|string $args
     * @param bool|string  $scheme
     *
     * @return string
     */
    public function createUrl($args, $scheme = false)
    {
        $context = $this->context;

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

        $url = $this->alias->get('@web') . $this->prefix . '/' . lcfirst($ca);
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
