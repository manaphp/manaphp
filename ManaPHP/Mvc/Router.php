<?php

namespace ManaPHP\Mvc;

use ManaPHP\Component;
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
     * @var array
     */
    protected $_groups = [];

    /**
     * @var bool
     */
    protected $_wasMatched = false;

    /**
     * Get rewrite info. This info is read from $_GET['_url'] or _SERVER["REQUEST_URI"].
     *
     * @param string $uri
     *
     * @return string
     * @throws \ManaPHP\Mvc\Router\Exception
     */
    public function getRewriteUri($uri = null)
    {
        if ($uri === null) {
            if ($this->request->hasQuery('_url')) {
                $uri = $this->request->getQuery('_url', 'ignore');
            } elseif ($this->request->hasServer('PATH_INFO')) {
                $uri = $this->request->getServer('PATH_INFO');
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
     * @throws \ManaPHP\Di\Exception
     * @throws \ManaPHP\Mvc\Router\Exception
     * @throws \ManaPHP\Mvc\Router\NotFoundRouteException
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

        $this->fireEvent('router:beforeCheckRoutes');

        foreach (array_reverse($this->_groups, true) as $module => $path) {
            if ($path[0] !== '/') {
                if (strpos($path, $host) !== 0) {
                    continue;
                }
                $path = $path === $host ? '/' : substr($path, strlen($host));
            }
            if (strpos($uri, $path) !== 0) {
                continue;
            }
            $handledUri = $path === '/' ? $uri : substr($uri, strlen($path));

            /**
             * @var \ManaPHP\Mvc\Router\Group $groupInstance
             */
            $groupInstance = $this->_dependencyInjector->getShared($this->alias->resolveNS('@ns.app\\' . $module . '\\RouteGroup'));

            $parts = $groupInstance->match($handledUri, $method);
            if ($parts !== false) {
                $this->_wasMatched = true;
                $this->_module = $module;
                $this->_controller = $parts['controller'];
                $this->_action = $parts['action'];
                $params = $parts['params'];
                unset($parts['controller'], $parts['action'], $parts['params']);

                $this->_params = array_merge($params, $parts);
                break;
            }
        }

        $this->fireEvent('router:afterCheckRoutes');

        return $this->_wasMatched;
    }

    /**
     * Mounts a group of routes in the router
     *
     * @param string $module
     * @param string $path
     *
     * @return static
     */
    public function mount($module, $path = null)
    {
        if (is_object($module)) {
            $parts = explode('\\', get_class($module));
            $this->_dependencyInjector->setShared(get_class($module), $module);
            $module = $parts[1];
        }

        if ($path === null) {
            $path = '/' . $module;
        }
        $path = rtrim($path, '/');

        $this->_groups[$module] = $path ?: '/';

        return $this;
    }

    /**
     * @return array
     */
    public function getMounted()
    {
        return $this->_groups;
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
     * @param string $path
     * @param array  $params
     *
     * @return string
     */
    public function createActionUrl($path, $params = [])
    {
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
            $module = Text::camelize($module);
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
            $module = $this->_module;
        }

        $url = rtrim($this->_modules[$module], '/') . '/' . lcfirst($ca);
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

        return $this->alias->resolve('@web' . $url);
    }
}