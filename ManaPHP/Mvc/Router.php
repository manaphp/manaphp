<?php

namespace ManaPHP\Mvc {

    use ManaPHP\Component;
    use ManaPHP\Mvc\Router\Exception;
    use ManaPHP\Mvc\Router\NotFoundRouteException;

    /**
     * ManaPHP\Mvc\Router
     *
     * <p>ManaPHP\Mvc\Router is the standard framework router. Routing is the
     * process of taking a URI endpoint (that part of the URI which comes after the base URL) and
     * decomposing it into parameters to determine which module, controller, and
     * action of that controller should receive the request</p>
     *
     *<code>
     *
     *    $router = new ManaPHP\Mvc\Router();
     *
     *  $router->add(
     *        "/documentation/{chapter}/{name}.{type:[a-z]+}",
     *        array(
     *            "controller" => "documentation",
     *            "action"     => "show"
     *        )
     *    );
     *
     *    $router->handle();
     *
     *    echo $router->getControllerName();
     *</code>
     *
     */
    class Router extends Component implements RouterInterface
    {
        /**
         * @var string
         */
        protected $_module = null;

        /**
         * @var string
         */
        protected $_controller = null;

        /**
         * @var string
         */
        protected $_action = null;

        /**
         * @var array
         */
        protected $_params = [];

        /**
         * @var \ManaPHP\Mvc\Router\GroupInterface[]
         */
        protected $_groups = [];

        /**
         * @var boolean
         */
        protected $_wasMatched = false;

        /**
         * @var string
         */
        protected $_defaultController = 'index';

        /**
         * @var string
         */
        protected $_defaultAction = 'index';

        /**
         * @var array
         */
        protected $_defaultParams = [];

        /**
         * @var boolean
         */
        protected $_removeExtraSlashes = false;

        /**
         * Get rewrite info. This info is read from $_GET['_url'] or _SERVER["REQUEST_URI"].
         *
         * @return string
         * @throws \ManaPHP\Mvc\Router\Exception
         */
        public function getRewriteUri()
        {
            if (isset($_GET['_url'])) {
                $url = $_GET['_url'];
            } elseif (isset($_SERVER['REQUEST_URI'])) {
                $url = explode('?', $_SERVER['REQUEST_URI'])[0];
            } else {
                throw new Exception('Get rewrite info failed');
            }

            return $url;
        }

        /**
         * Set whether router must remove the extra slashes in the handled routes
         *
         * @param boolean $remove
         *
         * @return static
         */
        public function removeExtraSlashes($remove)
        {
            $this->_removeExtraSlashes = $remove;

            return $this;
        }

        /**
         * @param string                               $uri
         * @param \ManaPHP\Mvc\Router\RouteInterface[] $routes
         * @param array                                $parts
         *
         * @return bool
         */
        protected function _findMatchedRoute($uri, $routes, &$parts)
        {
            $parts = [];

            for ($i = count($routes) - 1; $i >= 0; $i--) {
                $route = $routes[$i];

                if ($route->isMatched($uri, $matches)) {
                    foreach ($matches as $k => $v) {
                        if (is_string($k)) {
                            $parts[$k] = $v;
                        }
                    }

                    foreach ($route->getPaths() as $k => $v) {
                        if (is_int($v)) {
                            if (isset($matches[$v])) {
                                $parts[$k] = $matches[$v];
                            }
                        } else {
                            $parts[$k] = $v;
                        }
                    }

                    return true;
                }
            }

            return false;
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
         * @param string $host
         * @param bool   $silent
         *
         * @return boolean
         * @throws \ManaPHP\Mvc\Router\Exception
         */
        public function handle($uri = null, $host = null, $silent = true)
        {
            if ($uri === null) {
                $uri = $this->getRewriteUri();
            }

            if ($this->_removeExtraSlashes) {
                $uri = rtrim($uri, '/');
            }
            $refined_uri = $uri === '' ? '/' : $uri;

            $this->fireEvent('router:beforeCheckRoutes');

            $module = null;
            $route_found = false;
            for ($i = count($this->_groups) - 1; $i >= 0; $i--) {
                /**
                 * @var \ManaPHP\Mvc\Router\Group $group
                 */
                list($path, $module, $group) = $this->_groups[$i];

                $pos = strpos($path, '/');
                if ($pos === 0) {
                    if (stripos($refined_uri, $path) !== 0) {
                        continue;
                    }
                    $handle_uri = substr($refined_uri, strlen($path) - 1);
                } else {
                    if (!isset($refined_host)) {
                        if ($host === null) {
                            if (isset($_SERVER['HTTP_HOST'])) {
                                $refined_host = $_SERVER['HTTP_HOST'];
                            } else {
                                throw new Exception('router handle need host, but can not fetch.');
                            }
                        } else {
                            $refined_host = $host;
                        }
                    }

                    if (stripos($refined_host . $refined_uri, $path) !== 0) {
                        continue;
                    }

                    $handle_uri = substr($refined_uri, strlen($path) - $pos - 1);
                }

                $route_found = $this->_findMatchedRoute($handle_uri, $group->getRoutes(), $parts);
                if ($route_found) {
                    break;
                }
            }

            $this->_wasMatched = $route_found;

            if ($route_found) {

                $this->_module = $module;
                $this->_controller = $this->_defaultController;
                $this->_action = $this->_defaultAction;
                $this->_params = $this->_defaultParams;

                if (isset($parts['module'])) {
                    $this->_module = $parts['module'];
                    unset($parts['module']);
                }

                if (isset($parts['controller'])) {
                    $this->_controller = $parts['controller'];
                    unset($parts['controller']);
                }

                if (isset($parts['action'])) {
                    $this->_action = $parts['action'];
                    unset($parts['action']);
                }

                $params = [];
                if (isset($parts['params'])) {
                    if (is_string($parts['params'])) {
                        $params_str = trim($parts['params'], '/');
                        if ($params_str !== '') {
                            $params = explode('/', $params_str);
                        }
                    }

                    unset($parts['params']);
                }

                $this->_params = array_merge($params, $parts);
            }

            $this->fireEvent('router:afterCheckRoutes');

            if (!$route_found && !$silent) {
                throw new NotFoundRouteException('not found matched route: ' . $uri);
            }

            return $route_found;
        }

        /**
         * Mounts a group of routes in the router
         *
         * @param \ManaPHP\Mvc\Router\GroupInterface $group
         * @param string                             $module
         * @param string                             $path
         *
         * @return static
         */
        public function mount($group, $module, $path = null)
        {
            if ($path === null) {
                $path = '/' . $module;
            }

            $path = rtrim($path, '/') . '/';

            $this->_groups[] = [$path, $module, $group];

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
    }
}
