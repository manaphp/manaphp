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
     * @var array
     */
    protected $_modules = [];

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

        $this->fireEvent('router:beforeCheckRoutes');

        $module = null;
        $routeFound = false;
        for ($i = count($this->_groups) - 1; $i >= 0; $i--) {
            $group = &$this->_groups[$i];

            $path = $group['path'];
            $module = $group['module'];

            if ($path === '' || $path[0] === '/') {
                $checkedUri = $uri;
            } else {
                $checkedUri = $_SERVER['HTTP_HOST'] . $uri;
            }

            /**
             * strpos('/','')===false NOT true
             */
            if ($path !== '' && !Text::startsWith($checkedUri, $path)) {
                continue;
            }

            /**
             * substr('a',1)===false NOT ''
             */
            $handledUri = strlen($checkedUri) === strlen($path) ? '/' : substr($checkedUri, strlen($path));

            /**
             * @var \ManaPHP\Mvc\Router\Group $groupInstance
             */
            if ($group['groupInstance'] === null) {
                $group['groupInstance'] = $this->_dependencyInjector->get(class_exists($group['groupClassName']) ? $group['groupClassName'] : 'ManaPHP\Mvc\Router\Group');
            }
            $groupInstance = $group['groupInstance'];

            $parts = $groupInstance->match($handledUri, $method ?: $_SERVER['REQUEST_METHOD']);
            $routeFound = $parts !== false;
            if ($routeFound) {
                break;
            }
        }

        $this->_wasMatched = $routeFound;

        if ($routeFound) {
            $this->_module = $module;
            $this->_controller = isset($parts['controller']) ? $parts['controller'] : 'index';
            $this->_action = isset($parts['action']) ? $parts['action'] : 'index';

            $params = [];
            if (isset($parts['params'])) {
                $params_str = trim($parts['params'], '/');
                if ($params_str !== '') {
                    $params = explode('/', $params_str);
                }
            }

            unset($parts['controller'], $parts['action'], $parts['params']);

            $this->_params = array_merge($params, $parts);
        }

        $this->fireEvent('router:afterCheckRoutes');

        return $routeFound;
    }

    /**
     * Mounts a group of routes in the router
     *
     * @param string|\ManaPHP\Mvc\Router\GroupInterface $group
     * @param string                                    $path
     *
     * @return static
     */
    public function mount($group, $path = null)
    {
        if (is_object($group)) {
            $groupClassName = get_class($group);
            $groupInstance = $group;
        } else {
            $groupClassName = strrpos($group, '\\') ? $group : $this->alias->resolveNS("@ns.app\\$group\\RouteGroup");
            $groupInstance = null;
        }

        $parts = explode('\\', $groupClassName);
        unset($parts[0]);
        array_pop($parts);
        $module = implode('\\', $parts);

        if ($path === null) {
            $path = '/' . $module;
        }

        $path = rtrim($path, '/');

        $this->_groups[] = [
            'path' => $path,
            'module' => $module,
            'groupClassName' => $groupClassName,
            'groupInstance' => $groupInstance
        ];

        $this->_modules[$module] = $path ?: '/';

        return $this;
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
     * @return array
     */
    public function getModules()
    {
        return $this->_modules;
    }
}