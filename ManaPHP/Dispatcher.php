<?php

namespace ManaPHP;

use ManaPHP\Dispatcher\NotFoundActionException;
use ManaPHP\Dispatcher\NotFoundControllerException;
use ManaPHP\Utility\Text;
use ManaPHP\Validator\ValidateFailedException;
use ReflectionMethod;

class DispatcherContext
{
    /**
     * @var string
     */
    public $path;

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
     * @var \ManaPHP\Rest\Controller
     */
    public $controllerInstance;
}

/**
 * Class ManaPHP\Dispatcher
 *
 * @package dispatcher
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property \ManaPHP\DispatcherContext           $_context
 */
class Dispatcher extends Component implements DispatcherInterface
{
    /**
     * Gets last dispatched area name
     *
     * @return string
     */
    public function getArea()
    {
        return $this->_context->area;
    }

    /**
     * @param string $area
     *
     * @return static
     */
    public function setArea($area)
    {
        $context = $this->_context;

        $context->area = Text::camelize($area);

        return $this;
    }

    /**
     * Gets last dispatched controller name
     *
     * @return string
     */
    public function getController()
    {
        return $this->_context->controller;
    }

    /**
     * @param string $controller
     *
     * @return static
     */
    public function setController($controller)
    {
        $context = $this->_context;

        $context->controller = Text::camelize($controller);

        return $this;
    }

    /**
     * Gets the latest dispatched action name
     *
     * @return string
     */
    public function getAction()
    {
        return $this->_context->action;
    }

    /**
     * @param string $action
     *
     * @return static
     */
    public function setAction($action)
    {
        $context = $this->_context;

        $context->action = lcfirst(Text::camelize($action));

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
        $context = $this->_context;

        $context->params = $merge ? array_merge($context->params, $params) : $params;

        return $this;
    }

    /**
     * Gets action params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_context->params;
    }

    /**
     * Gets a param by its name or numeric index
     *
     * @param string|int $name
     * @param mixed     default
     *
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        $params = $this->_context->params;
        return $params[$name] ?? $default;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasParam($name)
    {
        return isset($this->_context->params[$name]);
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->_context->path;
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

        $parameters = (new ReflectionMethod($controller, $action . 'Action'))->getParameters();
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
            $errors = [];
            foreach ($missing as $field) {
                $errors[$field] = $this->validator->createError('required', $field);
            }
            throw new ValidateFailedException($errors);
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

        $this->eventsManager->fireEvent('request:authorize', $this);

        $this->eventsManager->fireEvent('request:validate', $this, ['controller' => get_class($controller), 'action' => $action]);

        $this->eventsManager->fireEvent('request:ready', $this);

        $this->eventsManager->fireEvent('request:invoke', $this, $action);

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

        $this->eventsManager->fireEvent('request:invoked', $this, ['action' => $action, 'return' => $r]);

        return $r;
    }

    public function invoke()
    {
        $context = $this->_context;

        return $this->invokeAction($context->controllerInstance, $context->action, $context->params);
    }

    /**
     * @return string
     */
    protected function _getControllerClassName()
    {
        $context = $this->_context;

        $area = $context->area;
        $controller = $context->controller;

        if ($area) {
            $controllerClassName = $this->alias->get('@ns.app') . "\\Controllers\\$area\\{$controller}Controller";
            if (class_exists($controllerClassName)) {
                return $controllerClassName;
            }

            $controllerClassName2 = $this->alias->get('@ns.app') . "\\Areas\\$area\\Controllers\\{$controller}Controller";
            if (class_exists($controllerClassName2)) {
                return $controllerClassName2;
            } else {
                throw new NotFoundControllerException(['both `:controller1` and `:controller2` class cannot be loaded',
                    'controller1' => $controllerClassName,
                    'controller2' => $controllerClassName2]);
            }
        } else {
            $controllerClassName = $this->alias->get('@ns.app') . "\\Controllers\\{$controller}Controller";
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
     * @param bool                     $auto_invoke
     *
     * @return mixed
     * @throws \ManaPHP\Dispatcher\NotFoundControllerException
     */
    public function dispatch($router, $auto_invoke = true)
    {
        $context = $this->_context;

        if ($router instanceof RouterContext) {
            $area = $router->area;
            $controller = $router->controller;
            $action = $router->action;
            $params = $router->params;
        } else {
            $area = $router->getArea();
            $controller = $router->getController();
            $action = $router->getAction();
            $params = $router->getParams();
        }

        if ($area) {
            $area = strpos($area, '_') === false ? ucfirst($area) : Text::camelize($area);
            $context->area = $area;
        }

        $controller = strpos($controller, '_') === false ? ucfirst($controller) : Text::camelize($controller);
        $context->controller = $controller;

        $action = strpos($action, '_') === false ? $action : lcfirst(Text::camelize($action));
        $context->action = $action;

        $context->params = $params;

        if ($area) {
            if ($action === 'index') {
                if ($controller === 'Index') {
                    $context->path = $area === 'Index' ? '/' : '/' . Text::underscore($area);
                } else {
                    $context->path = '/' . Text::underscore($area) . '/' . Text::underscore($controller);
                }
            } else {
                $context->path = '/' . Text::underscore($area) . '/' . Text::underscore($controller) . '/' . Text::underscore($action);
            }
        } else {
            if ($action === 'index') {
                $context->path = $controller === 'Index' ? '/' : '/' . Text::underscore($controller);
            } else {
                $context->path = '/' . Text::underscore($controller) . '/' . Text::underscore($action);
            }
        }

        $controllerClassName = $this->_getControllerClassName();

        /**
         * @var \ManaPHP\Rest\Controller $controllerInstance
         */
        $controllerInstance = $this->_di->getShared($controllerClassName);
        $context->controllerInstance = $controllerInstance;

        if ($auto_invoke) {
            return $this->invokeAction($controllerInstance, $action, $params);
        }
    }

    /**
     * @return \ManaPHP\Rest\Controller
     */
    public function getControllerInstance()
    {
        return $this->_context->controllerInstance;
    }
}
