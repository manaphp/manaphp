<?php

namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\Exception\MissingFieldException;
use ManaPHP\Mvc\Dispatcher\Exception as DispatcherException;
use ManaPHP\Mvc\Dispatcher\NotFoundControllerException;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Mvc\Dispatcher
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
     * @var \ManaPHP\Mvc\Controller
     */
    protected $_controllerInstance;

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
        $this->_controller = null;
        $this->_action = null;
        $this->_params = [];
        $this->_controllerInstance = null;
        $this->_returnedValue = null;
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
            $controllerName = $this->_controller;
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
     * @param \ManaPHP\Controller $controller
     * @param string              $action
     * @param array               $params
     *
     * @return array
     */
    protected function _buildActionArgs($controller, $action, $params)
    {
        $args = [];
        $missing = [];

        $di = $this->_di;

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
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

        if (count($missing) !== 0) {
            throw new MissingFieldException(['Missing required parameters: `:parameters`', 'parameters' => implode(',', $missing)]);
        }

        return $args;
    }

    /**
     * @param \ManaPHP\Controller $controller
     * @param string              $action
     * @param array               $params
     *
     * @return mixed
     */
    public function invokeAction($controller, $action, $params)
    {
        $actionMethod = $action . 'Action';

        if (!method_exists($controller, $actionMethod)) {
            throw new NotFoundException([
                '`:controller:::action` method does not exist',
                'action' => $actionMethod,
                'controller' => get_class($controller)
            ]);
        }

        if (method_exists($controller, 'beforeInvoke') && ($r = $controller->beforeInvoke($action)) !== null) {
            return $r;
        }

        if (($r = $this->fireEvent('dispatcher:beforeInvoke', $action)) !== null) {
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

        $this->fireEvent('dispatcher:afterInvoke', ['action' => $action, 'return' => $r]);

        if (method_exists($controller, 'afterInvoke')) {
            $controller->afterInvoke($action, $r);
        }

        return $r;
    }

    /**
     * Dispatches a handle action taking into account the routing parameters
     *
     * @param \ManaPHP\RouterInterface $router
     *
     * @return void
     * @throws \ManaPHP\Mvc\Dispatcher\NotFoundControllerException
     */
    public function dispatch($router)
    {
        $controller = $router->getController();
        $action = $router->getAction();

        if (($pos = strpos($controller, '/')) !== false) {
            $this->_controller = Text::camelize(substr($controller, 0, $pos + 1)) . Text::camelize(substr($controller, $pos + 1));
        } else {
            $this->_controller = strpos($controller, '_') === false ? ucfirst($controller) : Text::camelize($controller);
        }

        $this->_action = strpos($action, '_') === false ? $action : lcfirst(Text::camelize($action));
        $this->_params = $router->getParams();

        if ($this->fireEvent('dispatcher:beforeDispatch') === false) {
            return;
        }

        $controllerInstance = null;

        $controllerClassName = $this->getControllerClassName();

        if (!class_exists($controllerClassName) && !$this->_di->has($controllerClassName)) {
            throw new NotFoundControllerException(['`:controller` class cannot be loaded', 'controller' => $controllerClassName]);
        }

        /**
         * @var \ManaPHP\Mvc\Controller $controllerInstance
         */
        $controllerInstance = $this->_di->getShared($controllerClassName);
        $this->_controllerInstance = $controllerInstance;

        $this->_returnedValue = $this->invokeAction($controllerInstance, $this->_action, $this->_params);

        $this->fireEvent('dispatcher:afterDispatch');
    }

    /**
     * @return \ManaPHP\Mvc\Controller
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
