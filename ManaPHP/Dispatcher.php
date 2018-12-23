<?php

namespace ManaPHP;

use ManaPHP\Dispatcher\NotFoundActionException;
use ManaPHP\Dispatcher\NotFoundControllerException;
use ManaPHP\Exception\MissingRequiredFieldsException;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Dispatcher
 *
 * @package dispatcher
 * @property-read \ManaPHP\Http\FilterInterface  $filter
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Dispatcher extends Component implements DispatcherInterface
{
    /**
     * @var string
     */
    protected $_area;

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
     * @var \ManaPHP\Rest\Controller
     */
    protected $_controllerInstance;

    /**
     * @var mixed
     */
    protected $_returned_value;

    public function saveInstanceState()
    {
        return true;
    }

    public function restoreInstanceState($data)
    {
        $this->_area = null;
        $this->_controller = null;
        $this->_action = null;
        $this->_params = [];
        $this->_controllerInstance = null;
        $this->_returned_value = null;
    }

    /**
     * Gets last dispatched area name
     *
     * @return string
     */
    public function getArea()
    {
        return $this->_area;
    }

    /**
     * @param string $area
     *
     * @return static
     */
    public function setArea($area)
    {
        $this->_area = Text::camelize($area);

        return $this;
    }

    /**
     * Gets last dispatched controller name
     *
     * @return string
     */
    public function getController()
    {
        return $this->_controller;
    }

    /**
     * @param string $controller
     *
     * @return static
     */
    public function setController($controller)
    {
        $this->_controller = Text::camelize($controller);

        return $this;
    }

    /**
     * Gets the latest dispatched action name
     *
     * @return string
     */
    public function getAction()
    {
        return $this->_action;
    }

    /**
     * @param string $action
     *
     * @return static
     */
    public function setAction($action)
    {
        $this->_action = lcfirst(Text::camelize($action));

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
     * @param  mixed     default
     *
     * @return mixed
     */
    public function getParam($param, $default = null)
    {
        return isset($this->_params[$param]) ? $this->_params[$param] : $default;
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
        $this->_returned_value = $value;

        return $this;
    }

    /**
     * Returns value returned by the latest dispatched action
     *
     * @return mixed
     */
    public function getReturnedValue()
    {
        return $this->_returned_value;
    }

    /**
     * @param \ManaPHP\Rest\Controller $controller
     * @param string                   $action
     * @param array                    $params
     *
     * @return array
     */
    protected function _buildActionArgs($controller, $action, $params)
    {
        $args = [];
        $missing = [];

        $di = $this->_di;

        $parameters = (new \ReflectionMethod($controller, $action . 'Action'))->getParameters();
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $value = null;

            $type = $parameter->getClass();
            if ($type !== null) {
                $type = $type->getName();
            } elseif ($parameter->isDefaultValueAvailable()) {
                $type = gettype($parameter->getDefaultValue());
            }

            if ($className = ($c = $parameter->getClass()) ? $c->getName() : null) {
                if ($di->has($name)) {
                    $value = $di->get($name);
                } elseif ($di->has($className)) {
                    $value = $di->get($className);
                } else {
                    $value = $di->getShared($className);
                }
            } elseif (isset($params[$name])) {
                $value = $params[$name];
            } elseif ($this->request->has($name)) {
                $value = $this->request->get($name);
            } elseif (count($params) === 1 && count($parameters) === 1) {
                $value = $params[0];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            }

            if ($value === null) {
                $missing[] = $name;
                continue;
            }

            switch ($type) {
                case 'boolean':
                    $value = (bool)$value;
                    break;
                case 'integer':
                    $value = (int)$value;
                    break;
                case 'double':
                    $value = (float)$value;
                    break;
                case 'string':
                    $value = (string)$value;
                    break;
                case 'array':
                    $value = (array)$value;
                    break;
            }

            if ($parameter->isArray()) {
                $args[] = (array)$value;
            } else {
                $args[] = $value;
            }
        }

        if ($missing) {
            throw new MissingRequiredFieldsException($missing);
        }

        return $args;
    }

    /**
     * @param \ManaPHP\Rest\Controller $controller
     * @param string                   $action
     * @param array                    $params
     *
     * @return mixed
     */
    public function invokeAction($controller, $action, $params)
    {
        $actionMethod = $action . 'Action';

        if (!method_exists($controller, $actionMethod)) {
            throw new NotFoundActionException([
                '`:controller:::action` method does not exist',
                'action' => $actionMethod,
                'controller' => get_class($controller)
            ]);
        }

        if (method_exists($controller, 'beforeInvoke') && ($r = $controller->beforeInvoke($action)) !== null) {
            return $r;
        }

        if (($r = $this->eventsManager->fireEvent('dispatcher:beforeInvoke', $this, $action)) !== null) {
            return $r;
        }

        $args = $this->_buildActionArgs($controller, $action, $params);

        switch (count($args)) {
            case 0:
                $r = $controller->$actionMethod();
                break;
            case 1:
                $r = $controller->$actionMethod($args[0]);
                break;
            case 2:
                $r = $controller->$actionMethod($args[0], $args[1]);
                break;
            case 3:
                $r = $controller->$actionMethod($args[0], $args[1], $args[2]);
                break;
            default:
                $r = call_user_func_array([$controller, $actionMethod], $args);
                break;
        }

        $this->eventsManager->fireEvent('dispatcher:afterInvoke', $this, ['action' => $action, 'return' => $r]);

        if (method_exists($controller, 'afterInvoke')) {
            $controller->afterInvoke($action, $r);
        }

        return $r;
    }

    /**
     * @return string
     */
    protected function _getControllerClassName()
    {
        $area = $this->_area;
        $controller = $this->_controller;

        if ($area) {
            $controllerClassName = $this->alias->resolveNS("@ns.app\\Controllers\\$area\\{$controller}Controller");
            if (class_exists($controllerClassName)) {
                return $controllerClassName;
            }

            $controllerClassName2 = $this->alias->resolveNS("@ns.app\\Areas\\$area\\Controllers\\{$controller}Controller");
            if (class_exists($controllerClassName2)) {
                return $controllerClassName2;
            } else {
                throw new NotFoundControllerException(['both `:controller1` and `:controller2` class cannot be loaded',
                    'controller1' => $controllerClassName,
                    'controller2' => $controllerClassName2]);
            }
        } else {
            $controllerClassName = $this->alias->resolveNS("@ns.app\\Controllers\\{$controller}Controller");
            if (class_exists($controllerClassName)) {
                return $controllerClassName;
            } else {
                throw new NotFoundControllerException(['`:controller` class cannot be loaded', 'controller' => $controllerClassName]);
            }
        }
    }

    /**
     * Dispatches a handle action taking into account the routing parameters
     *
     * @param \ManaPHP\RouterInterface $router
     *
     * @return void
     * @throws \ManaPHP\Dispatcher\NotFoundControllerException
     */
    public function dispatch($router)
    {
        if ($area = $router->getArea()) {
            $this->_area = strpos($area, '_') === false ? ucfirst($area) : Text::camelize($area);
        }

        $controller = $router->getController();
        $this->_controller = strpos($controller, '_') === false ? ucfirst($controller) : Text::camelize($controller);

        $action = $router->getAction();
        $this->_action = strpos($action, '_') === false ? $action : lcfirst(Text::camelize($action));

        $this->_params = $router->getParams();

        if ($this->eventsManager->fireEvent('dispatcher:beforeDispatch', $this) === false) {
            return;
        }

        $controllerClassName = $this->_getControllerClassName();

        /**
         * @var \ManaPHP\Rest\Controller $controllerInstance
         */
        $controllerInstance = $this->_di->getShared($controllerClassName);
        $this->_controllerInstance = $controllerInstance;

        $this->_returned_value = $this->invokeAction($controllerInstance, $this->_action, $this->_params);

        $this->eventsManager->fireEvent('dispatcher:afterDispatch', $this);
    }

    /**
     * @return \ManaPHP\Rest\Controller
     */
    public function getControllerInstance()
    {
        return $this->_controllerInstance;
    }

    /**
     * @param string $glue
     *
     * @return string
     */
    public function getMCA($glue = '/')
    {
        return Text::underscore($this->_controller) . $glue . Text::underscore($this->_action);
    }
}
