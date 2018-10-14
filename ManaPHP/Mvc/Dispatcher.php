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
 * @property-read \ManaPHP\Http\FilterInterface   $filter
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\ActionInvokerInterface $actionInvoker
 */
class Dispatcher extends Component implements DispatcherInterface
{
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


    public function saveInstanceState()
    {
        return true;
    }

    public function restoreInstanceState($data)
    {
        $this->_controllerName = null;
        $this->_actionName = null;
        $this->_params = [];
        $this->_controller = null;
        $this->_returnedValue = null;
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

        if (method_exists($controllerInstance, 'beforeExecuteRoute') && $controllerInstance->beforeExecuteRoute() === false) {
            return false;
        }

        $this->_returnedValue = $this->actionInvoker->invoke($controllerInstance, $this->_actionName, $this->_params);


        if ($this->fireEvent('dispatcher:afterExecuteRoute') === false) {
            return false;
        }

        if (method_exists($controllerInstance, 'afterExecuteRoute')) {
            $controllerInstance->afterExecuteRoute();
        }

        $this->fireEvent('dispatcher:afterDispatch');

        return true;
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
     * @param string $glue
     *
     * @return string
     */
    public function getMCA($glue = '/')
    {
        return Text::underscore($this->_controllerName) . $glue . Text::underscore($this->_actionName);
    }
}
