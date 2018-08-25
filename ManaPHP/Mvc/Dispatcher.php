<?php

namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\Mvc\Dispatcher\Exception as DispatcherException;
use ManaPHP\Mvc\Dispatcher\NotFoundControllerException;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Mvc\Dispatcher
 *
 * @package dispatcher
 * @property \ManaPHP\Http\FilterInterface   $filter
 * @property \ManaPHP\Http\RequestInterface  $request
 * @property \ManaPHP\ActionInvokerInterface $actionInvoker
 */
class Dispatcher extends Component implements DispatcherInterface
{
    /**
     * @var bool
     */
    protected $_finished = false;

    /**
     * @var bool
     */
    protected $_forwarded = false;

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
     * @var \ManaPHP\Mvc\Controller
     */
    protected $_controller;

    /**
     * @var mixed
     */
    protected $_returnedValue;

    /**
     * @var string
     */
    protected $_previousControllerName;

    /**
     * @var string
     */
    protected $_previousActionName;

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
     * @param  string|int $param
     * @param  string     $rule
     *
     * @return mixed
     * @throws \ManaPHP\Mvc\Dispatcher\Exception
     */
    public function getParam($param, $rule = null)
    {
        if (!isset($this->_params[$param])) {
            throw new DispatcherException(['`:param` param is not exists', 'param' => $param]);
        }

        return $rule ? $this->filter->sanitize($param, $rule, $this->_params[$param]) : $this->_params[$param];
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
     * @param string $controllerName
     *
     * @return string
     */
    public function getControllerClassName($controllerName = null)
    {
        if (!$controllerName) {
            $controllerName = $this->_controllerName;
        }

        if (($pos = strpos($controllerName, '/')) !== false) {
            $area = substr($controllerName, 0, $pos);
            $controller = substr($controllerName, $pos + 1);
            return $this->alias->resolveNS("@ns.app\\Areas\\$area\\Controllers\\{$controller}Controller");
        } else {
            return $this->alias->resolveNS("@ns.app\\Controllers\\{$controllerName}Controller");
        }
    }

    /**
     * Dispatches a handle action taking into account the routing parameters
     *
     * @param string $controller
     * @param string $action
     * @param array  $params
     *
     * @return bool
     * @throws \ManaPHP\Mvc\Dispatcher\Exception
     * @throws \ManaPHP\Mvc\Dispatcher\NotFoundControllerException
     */
    public function dispatch($controller, $action, $params = [])
    {
        if (($pos = strpos($controller, '/')) !== false) {
            $this->_controllerName = Text::camelize(substr($controller, 0, $pos + 1)) . Text::camelize(substr($controller, $pos + 1));
        } else {
            $this->_controllerName = strpos($controller, '_') === false ? ucfirst($controller) : Text::camelize($controller);
        }

        $this->_actionName = strpos($action, '_') === false ? $action : lcfirst(Text::camelize($action));
        $this->_params = $params;

        if ($this->fireEvent('dispatcher:beforeDispatch') === false) {
            return false;
        }

        $controllerInstance = null;
        $numberDispatches = 0;
        $this->_finished = false;

        while ($this->_finished === false) {
            // if the user made a forward in the listener,the $this->_finished will be changed to false.
            $this->_finished = true;

            if ($numberDispatches++ === 32) {
                throw new DispatcherException('dispatcher has detected a cyclic routing causing stability problems');
            }

            $controllerClassName = $this->getControllerClassName();

            if (!class_exists($controllerClassName) && !$this->_di->has($controllerClassName)) {
                throw new NotFoundControllerException(['`:controller` class cannot be loaded', 'controller' => $controllerClassName]);
            }

            /**
             * @var \ManaPHP\Mvc\ControllerInterface $controllerInstance
             */
            $controllerInstance = $this->_di->getShared($controllerClassName);
            $this->_controller = $controllerInstance;

            if ($this->fireEvent('dispatcher:beforeExecuteRoute') === false) {
                return false;
            }

            if ($this->_finished === false) {
                continue;
            }

            // Calling beforeExecuteRoute as callback
            if (method_exists($controllerInstance, 'beforeExecuteRoute')) {
                if ($controllerInstance->beforeExecuteRoute() === false) {
                    continue;
                }

                if ($this->_finished === false) {
                    continue;
                }
            }

            $this->_returnedValue = $this->actionInvoker->invoke($controllerInstance, $this->_actionName, $this->_params);
            if ($this->_finished === false) {
                continue;
            }

            if ($this->fireEvent('dispatcher:afterExecuteRoute') === false) {
                return false;
            }

            if ($this->_finished === false) {
                continue;
            }

            if (method_exists($controllerInstance, 'afterExecuteRoute')) {
                if ($controllerInstance->afterExecuteRoute() === false) {
                    continue;
                }

                if ($this->_finished === false) {
                    continue;
                }
            }
        }

        $this->fireEvent('dispatcher:afterDispatch');

        return true;
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
                $this->_actionName = strpos($parts[0], '_') === false ? $parts[0] : lcfirst(Text::camelize($parts[0]));
                break;
            case 2:
                $this->_previousControllerName = $this->_controllerName;
                $this->_controllerName = strpos($parts[0], '_') === false ? ucfirst($parts[0]) : Text::camelize($parts[0]);
                $this->_previousActionName = $this->_actionName;
                $this->_actionName = strpos($parts[1], '_') === false ? $parts[1] : lcfirst(Text::camelize($parts[1]));
                break;
            default:
                throw new DispatcherException(['`:forward` forward format is invalid', 'forward' => $forward]);
        }

        $this->_params = array_merge($this->_params, $params);

        $this->_finished = false;
        $this->_forwarded = true;
    }

    /**
     * Check if the current executed action was forwarded by another one
     *
     * @return bool
     */
    public function wasForwarded()
    {
        return $this->_forwarded;
    }

    /**
     * @return \ManaPHP\Mvc\Controller
     */
    public function getController()
    {
        return $this->_controller;
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

    /**
     * @param string $glue
     *
     * @return string
     */
    public function getMCA($glue = '/')
    {
        return Text::underscore($this->_controllerName) . $glue . Text::underscore($this->_actionName);
    }

    public function saveInstanceState()
    {
        $state = get_object_vars($this);
        unset($state['_di'], $state['_traced']);

        return $state;
    }
}
