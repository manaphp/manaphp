<?php

namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\Mvc\Dispatcher\Exception;
use ManaPHP\Mvc\Dispatcher\NotFoundActionException;
use ManaPHP\Mvc\Dispatcher\NotFoundControllerException;
use ManaPHP\Utility\Text;

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
     * @param string $actionName
     *
     * @return static
     */
    public function setActionName($actionName)
    {
        $this->_actionName = lcfirst(Text::camelize($actionName));

        return $this;
    }

    /**
     * @param array $params
     * @param bool  $merge
     *
     * @return static
     */
    public function setParams($params, $merge = true)
    {
        $this->_params = $merge ? array_merge($this->_params, $params) : $params;

        return $this;
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

        return null;
    }

    /**
     * @param string $param
     *
     * @return bool
     */
    public function hasParam($param)
    {
        return isset($this->_params[$param]);
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
     * @throws \ManaPHP\Mvc\Dispatcher\Exception|\ManaPHP\Mvc\Dispatcher\NotFoundControllerException|\ManaPHP\Mvc\Dispatcher\NotFoundActionException
     */
    public function dispatch($module, $controller, $action, $params = [])
    {
        $this->_moduleName = Text::camelize($module);
        $this->_controllerName = Text::camelize($controller);
        $this->_actionName = lcfirst(Text::camelize($action));

        $this->_params = $params;

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

            if ($this->fireEvent('dispatcher:beforeDispatch') === false) {
                return false;
            }

            if ($this->_finished === false) {
                continue;
            }

            $controllerClassName = $this->_rootNamespace . '\\' . $this->_moduleName . '\\Controllers\\' . $this->_controllerName . $this->_controllerSuffix;

            if (!$this->_dependencyInjector->has($controllerClassName) && !class_exists($controllerClassName)) {
                throw new NotFoundControllerException($controllerClassName . ' handler class cannot be loaded');
            }

            $controllerInstance = $this->_dependencyInjector->getShared($controllerClassName);

            if (!is_object($controllerInstance)) {
                throw new Exception('Invalid handler type returned from the services container: ' . gettype($controllerInstance));
            }

            $hasAction = false;
            $actionMethod = strtolower($this->_actionName . $this->_actionSuffix);
            foreach (get_class_methods($controllerInstance) as $method) {
                if ($actionMethod === strtolower($method)) {
                    if (substr($method, -strlen($this->_actionSuffix)) !== $this->_actionSuffix) {
                        throw new Exception("The action '$method' of {$this->_controllerName}{$this->_controllerSuffix}  does not suffix with '{$this->_actionSuffix}' case sensitively, please amend it first.");
                    }

                    if (strtolower($method[0]) !== $method[0]) {
                        throw new Exception("The action '$method' of {$this->_controllerName}{$this->_controllerSuffix}  does not prefix with lowercase character, please amend it first.");
                    }

                    $hasAction = true;
                    $this->_actionName = substr($method, 0, -strlen($this->_actionSuffix));
                    $actionMethod = $method;
                    break;
                }
            }

            if (!$hasAction) {
                throw new NotFoundActionException('Action \'' . $this->_actionName . $this->_actionSuffix . '\' was not found on handler \'' . $controllerClassName . '\'');
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

            if ($this->fireEvent('dispatcher:beforeExecuteRoute') === false) {
                return false;
            }

            if ($this->_finished === false) {
                continue;
            }

            if (!in_array($controllerClassName, $this->_initializedControllers,
                    true) && method_exists($controllerInstance, 'initialize')
            ) {
                $controllerInstance->initialize();
                $this->_initializedControllers[] = $controllerClassName;
            }

            $this->_returnedValue = call_user_func_array([$controllerInstance, $actionMethod], $this->_params);

            // Call afterDispatch
            $this->fireEvent('dispatcher:afterDispatch');

            if ($this->fireEvent('dispatcher:afterExecuteRoute') === false) {
                return false;
            }

            if ($this->_finished === false) {
                continue;
            }

            if (method_exists($controllerInstance, 'afterExecuteRoute')) {
                if ($controllerInstance->afterExecuteRoute($this) === false) {
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
     *  $this->dispatcher->forward('posts/index'));
     *</code>
     *
     * @param string $forward
     * @param array  $params
     *
     * @throws \ManaPHP\Mvc\Dispatcher\Exception
     */
    public function forward($forward, $params = [])
    {
        $parts = explode('/', $forward);
        switch (count($parts)) {
            case 1:
                $this->_previousActionName = $this->_actionName;
                $this->_actionName = lcfirst(Text::camelize($parts[0]));
                break;
            case 2:
                $this->_previousControllerName = $this->_controllerName;
                $this->_controllerName = Text::camelize($parts[0]);
                $this->_previousActionName = $this->_actionName;
                $this->_actionName = lcfirst(Text::camelize($parts[1]));
                break;
            default:
                throw new Exception('forward format is invalid: ' . $forward);
        }

        $this->_params = array_merge($this->_params, $params);

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
     * Gets last dispatched controller name
     *
     * @return string
     */
    public function getControllerName()
    {
        return $this->_controllerName;
    }

    /**
     * @param string $controllerName
     *
     * @return static
     */
    public function setControllerName($controllerName)
    {
        $this->_controllerName = Text::camelize($controllerName);

        return $this;
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
