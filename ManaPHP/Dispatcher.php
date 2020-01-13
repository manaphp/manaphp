<?php

namespace ManaPHP;

use ManaPHP\Dispatcher\NotFoundActionException;
use ManaPHP\Dispatcher\NotFoundControllerException;
use ManaPHP\Helper\Str;

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
     * @var \ManaPHP\Controller
     */
    public $controllerInstance;

    /**
     * @var bool
     */
    public $isInvoking = false;
}

/**
 * Class ManaPHP\Dispatcher
 *
 * @package dispatcher
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property-read \ManaPHP\DispatcherContext      $_context
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

        $context->area = Str::camelize($area);

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

        $context->controller = Str::camelize($controller);

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

        $context->action = lcfirst(Str::camelize($action));

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
     * @param \ManaPHP\Controller $controller
     * @param string              $action
     *
     * @return mixed
     */
    public function invokeAction($controller, $action)
    {
        $actionMethod = $action . 'Action';

        if (!$controller->isInvokable($action)) {
            throw new NotFoundActionException([
                '`:controller:::action` method does not exist',
                'action' => $actionMethod,
                'controller' => get_class($controller)
            ]);
        }

        $event_data = ['controller' => $controller, 'action' => $action];

        $this->fireEvent('request:authorize', $event_data);

        $this->fireEvent('request:validate', $event_data);

        $this->fireEvent('request:ready', $event_data);

        $this->fireEvent('request:invoking', $event_data);

        try {
            $context = $this->_context;
            $context->isInvoking = true;
            $r = $controller->invoke($action);
        } finally {
            $context->isInvoking = false;
        }

        $this->fireEvent('request:invoked', ['controller' => $controller, 'action' => $action, 'return' => $r]);

        return $r;
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
            $controllerClassName = "App\\Controllers\\$area\\{$controller}Controller";
            if (class_exists($controllerClassName)) {
                return $controllerClassName;
            }

            $controllerClassName2 = "App\\Areas\\$area\\Controllers\\{$controller}Controller";
            if (class_exists($controllerClassName2)) {
                return $controllerClassName2;
            } else {
                throw new NotFoundControllerException(['both `:controller1` and `:controller2` class cannot be loaded',
                    'controller1' => $controllerClassName,
                    'controller2' => $controllerClassName2]);
            }
        } else {
            $controllerClassName = "App\\Controllers\\{$controller}Controller";
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
     * @return mixed
     * @throws \ManaPHP\Dispatcher\NotFoundControllerException
     */
    public function dispatch($router)
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

        $globals = $this->request->getGlobals();

        foreach ($params as $k => $v) {
            if (is_string($k)) {
                $globals->_REQUEST[$k] = $v;
            }
        }

        if (isset($params[0])) {
            $globals->_REQUEST['id'] = $params[0];
        }

        if ($area) {
            $area = strpos($area, '_') === false ? ucfirst($area) : Str::camelize($area);
            $context->area = $area;
        }

        $controller = strpos($controller, '_') === false ? ucfirst($controller) : Str::camelize($controller);
        $context->controller = $controller;

        $action = strpos($action, '_') === false ? $action : lcfirst(Str::camelize($action));
        $context->action = $action;

        $context->params = $params;

        if ($area) {
            if ($action === 'index') {
                if ($controller === 'Index') {
                    $context->path = $area === 'Index' ? '/' : '/' . Str::underscore($area);
                } else {
                    $context->path = '/' . Str::underscore($area) . '/' . Str::underscore($controller);
                }
            } else {
                $context->path = '/' . Str::underscore($area) . '/' . Str::underscore($controller) . '/' . Str::underscore($action);
            }
        } else {
            if ($action === 'index') {
                $context->path = $controller === 'Index' ? '/' : '/' . Str::underscore($controller);
            } else {
                $context->path = '/' . Str::underscore($controller) . '/' . Str::underscore($action);
            }
        }

        $controllerClassName = $this->_getControllerClassName();

        /** @var \ManaPHP\Controller $controllerInstance */
        $controllerInstance = $this->_di->getShared($controllerClassName);
        $context->controllerInstance = $controllerInstance;

        return $this->invokeAction($controllerInstance, $action);
    }

    /**
     * @return \ManaPHP\Controller
     */
    public function getControllerInstance()
    {
        return $this->_context->controllerInstance;
    }

    /**
     * @return bool
     */
    public function isInvoking()
    {
        return $this->_context->isInvoking;
    }
}
