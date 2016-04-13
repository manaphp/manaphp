<?php

namespace ManaPHP\Mvc {

    use ManaPHP\Component;
    use ManaPHP\Mvc\Dispatcher\Exception;
    use ManaPHP\Mvc\Dispatcher\NotFoundActionException;
    use ManaPHP\Mvc\Dispatcher\NotFoundControllerException;

    /**
     * ManaPHP\Mvc\Dispatcher
     *
     * Dispatching is the process of taking the request object, extracting the module name,
     * controller name, action name, and optional parameters contained in it, and then
     * instantiating a controller and calling an action of that controller.
     *
     *<code>
     *
     *    $di = new ManaPHP\Di();
     *
     *    $dispatcher = new ManaPHP\Mvc\Dispatcher();
     *
     *  $dispatcher->setDI($di);
     *
     *  $controller = $dispatcher->dispatch('app','posts','index');
     *
     *</code>
     */
    class Dispatcher extends Component implements DispatcherInterface
    {
        /**
         * @var boolean
         */
        protected $_finished = false;

        /**
         * @var boolean
         */
        protected $_forwarded = false;

        /**
         * @var string
         */
        protected $_moduleName;

        /**
         * @var string
         */
        protected $_rootNamespace;

        /**
         * @var string
         */
        protected $_controllerName;

        /**
         * @var string
         */
        protected $_actionName;

        /**
         * @var array
         */
        protected $_params = [];

        /**
         * @var mixed
         */
        protected $_returnedValue;

        /**
         * @var string
         */
        protected $_controllerSuffix = 'Controller';

        /**
         * @var string
         */
        protected $_actionSuffix = 'Action';

        /**
         * @var string
         */
        protected $_previousControllerName;

        /**
         * @var string
         */
        protected $_previousActionName;

        /**
         * @var array
         */
        protected $_initializedControllers = [];

        /**
         * Sets the namespace where the controller class is
         *
         * @param string $namespaceName
         *
         * @return static
         */
        public function setRootNamespace($namespaceName)
        {
            $this->_rootNamespace = $namespaceName;

            return $this;
        }

        /**
         * Gets a namespace to be prepended to the current handler name
         *
         * @return string
         */
        public function getRootNamespace()
        {
            return $this->_rootNamespace;
        }

        /**
         * Gets the module where the controller class is
         *
         * @return string
         */
        public function getModuleName()
        {
            return $this->_moduleName;
        }

        /**
         * Gets the latest dispatched action name
         *
         * @return string
         */
        public function getActionName()
        {
            return $this->_actionName;
        }

        /**
         * Gets action params
         *
         * @return array
         */
        public function getParams()
        {
            return $this->_params;
        }

        /**
         * Gets a param by its name or numeric index
         *
         * @param  string|int   $param
         * @param  string|array $filters
         * @param  mixed        $defaultValue
         *
         * @return mixed
         * @throws \ManaPHP\Mvc\Dispatcher\Exception
         */
        public function getParam($param, $filters = null, $defaultValue = null)
        {
            if (!isset($this->_params[$param])) {
                return $defaultValue;
            }

            if ($filters === null) {
                return $this->_params[$param];
            }

            if (!is_object($this->_dependencyInjector)) {
                throw new Exception("A dependency injection object is required to access the 'filter' service");
            }

            return null;
        }

        /**
         * Sets the latest returned value by an action manually
         *
         * @param mixed $value
         *
         * @return static
         */
        public function setReturnedValue($value)
        {
            $this->_returnedValue = $value;

            return $this;
        }

        /**
         * Returns value returned by the latest dispatched action
         *
         * @return mixed
         */
        public function getReturnedValue()
        {
            return $this->_returnedValue;
        }

        /**
         * Dispatches a handle action taking into account the routing parameters
         *
         * @param string $module
         * @param string $controller
         * @param string $action
         * @param array  $params
         *
         * @return false|\ManaPHP\Mvc\ControllerInterface
         * @throws \ManaPHP\Mvc\Dispatcher\Exception
         */
        public function dispatch($module, $controller, $action, $params = null)
        {
            $this->_moduleName = $this->_camelize($module);
            $this->_controllerName = $this->_camelize($controller);
            $this->_actionName = lcfirst($this->_camelize($action));

            $this->_params = $params === null ? [] : $params;

            if ($this->fireEvent('dispatcher:beforeDispatchLoop') === false) {
                return false;
            }

            $controllerInstance = null;
            $numberDispatches = 0;
            $this->_finished = false;

            while ($this->_finished === false) {
                // if the user made a forward in the listener,the $this->_finished will be changed to false.
                $this->_finished = true;

                if ($numberDispatches++ === 32) {
                    throw new Exception('Dispatcher has detected a cyclic routing causing stability problems');
                }

                $this->fireEvent('dispatcher:beforeDispatch');

                if ($this->_finished === false) {
                    continue;
                }

                $controllerClassName = '';
                if ($this->_rootNamespace !== null && $this->_rootNamespace !== '') {
                    $controllerClassName .= $this->_rootNamespace . '\\';
                }
                if ($this->_rootNamespace !== null && $this->_moduleName !== '') {
                    $controllerClassName .= $this->_moduleName . '\\Controllers\\';
                }
                $controllerClassName .= $this->_controllerName . $this->_controllerSuffix;

                if (!$this->_dependencyInjector->has($controllerClassName) && !class_exists($controllerClassName)) {
                    if ($this->fireEvent('dispatcher:beforeNotFoundController') === false) {
                        return false;
                    }

                    if ($this->_finished === false) {
                        continue;
                    }

                    throw new NotFoundControllerException($controllerClassName . ' handler class cannot be loaded');
                }

                $controllerInstance = $this->_dependencyInjector->getShared($controllerClassName);

                if (!is_object($controllerInstance)) {
                    throw new Exception('Invalid handler type returned from the services container: ' . gettype($controllerInstance));
                }

                $actionMethod = $this->_actionName . $this->_actionSuffix;
                if (!method_exists($controllerInstance, $actionMethod)) {
                    if ($this->fireEvent('dispatcher:beforeNotFoundAction') === false) {
                        continue;
                    }

                    if ($this->_finished === false) {
                        continue;
                    }

                    throw new NotFoundActionException('Action \'' . $this->_actionName . '\' was not found on handler \'' . $controllerClassName . '\'');
                }

                // Calling beforeExecuteRoute as callback
                if (method_exists($controllerInstance, 'beforeExecuteRoute')) {
                    if ($controllerInstance->beforeExecuteRoute($this) === false) {
                        continue;
                    }

                    if ($this->_finished === false) {
                        continue;
                    }
                }

                if (!in_array($controllerClassName, $this->_initializedControllers,
                        true) && method_exists($controllerInstance, 'initialize')
                ) {
                    $controllerInstance->initialize();
                    $this->_initializedControllers[] = $controllerClassName;
                }

                $this->_returnedValue = call_user_func_array([$controllerInstance, $actionMethod], $this->_params);

                $value = null;

                // Call afterDispatch
                $this->fireEvent('dispatcher:afterDispatch');

                if (method_exists($controllerInstance, 'afterExecuteRoute')) {
                    if ($controllerInstance->afterExecuteRoute($this, $value) === false) {
                        continue;
                    }

                    if ($this->_finished === false) {
                        continue;
                    }
                }
            }

            $this->fireEvent('dispatcher:afterDispatchLoop');

            return $controllerInstance;
        }

        /**
         * Forwards the execution flow to another controller/action
         * Dispatchers are unique per module. Forwarding between modules is not allowed
         *
         *<code>
         *  $this->dispatcher->forward(array('controller' => 'posts', 'action' => 'index'));
         *</code>
         *
         * @param string|array $forward
         *
         * @throws \ManaPHP\Mvc\Dispatcher\Exception
         */
        public function forward($forward)
        {
            if (is_string($forward)) {
                if ($forward[0] === '/') {
                    throw new Exception('Forward path starts with / character is confused, please remove it');
                }

                $_forward = [];
                list($_forward['module'], $_forward['controller'], $_forward['action']) = array_pad(explode('/', $forward), -3, null);
                $forward = $_forward;
            }

            if (isset($forward['module'])) {
                $this->_moduleName = $this->_camelize($forward['module']);
            }

            if (isset($forward['controller'])) {
                $this->_previousControllerName = $this->_controllerName;
                $this->_controllerName = $this->_camelize($forward['controller']);
            }

            if (isset($forward['action'])) {
                $this->_previousActionName = $this->_actionName;
                $this->_actionName = lcfirst($this->_camelize($forward['action']));
            }

            if (isset($forward['params'])) {
                $this->_params = $forward['params'];
            }

            $this->_finished = false;
            $this->_forwarded = true;
        }

        /**
         * Check if the current executed action was forwarded by another one
         *
         * @return boolean
         */
        public function wasForwarded()
        {
            return $this->_forwarded;
        }

        /**
         * @param string $str
         *
         * @return string
         */
        protected function _camelize($str)
        {
            if (strpos($str, '_') !== false) {
                $parts = explode('_', $str);
                foreach ($parts as &$v) {
                    $v = ucfirst($v);
                }

                return implode('', $parts);
            } else {
                return ucfirst($str);
            }
        }

        /**
         * Gets last dispatched controller name
         *
         * @return string
         */
        public function getControllerName()
        {
            return $this->_controllerName;
        }

        /**
         * Returns the previous controller in the dispatcher
         *
         * @return string
         */
        public function getPreviousControllerName()
        {
            return $this->_previousControllerName;
        }

        /**
         * Returns the previous action in the dispatcher
         *
         * @return string
         */
        public function getPreviousActionName()
        {
            return $this->_previousActionName;
        }
    }
}
