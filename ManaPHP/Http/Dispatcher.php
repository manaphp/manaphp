<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Helper\Str;
use ManaPHP\Http\Dispatcher\NotFoundControllerException;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

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
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property-read \ManaPHP\Http\DispatcherContext $_context
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

        $context->action = Str::variablize($action);

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
        $context = $this->_context;

        if ($context->path === null) {
            $area = $context->area;
            $controller = $context->controller;
            $action = $context->action;

            if ($action === 'index') {
                $path = $controller === 'Index' ? '/' : '/' . Str::underscore($controller);
            } else {
                $path = '/' . Str::underscore($controller) . '/' . Str::underscore($action);
            }

            if ($area !== '' && $area !== null) {
                if ($area === 'Index' && $path === '/') {
                    null;
                } else {
                    $path = '/' . Str::underscore($area) . $path;
                }
            }

            $context->path = $path;
        }

        return $context->path;
    }

    /**
     * @param \ManaPHP\Controller $controller
     * @param string              $action
     *
     * @return mixed
     */
    public function invokeAction($controller, $action)
    {
        $controller->validateInvokable($action);

        $event_data = ['controller' => $controller, 'action' => $action];

        $this->fireEvent('request:authorize', $event_data);

        $this->fireEvent('request:validate', $event_data);

        $this->fireEvent('request:ready', $event_data);

        $this->fireEvent('request:invoking', $event_data);

        try {
            $context = $this->_context;
            $context->isInvoking = true;
            $return = $controller->invoke($action);
        } finally {
            $context->isInvoking = false;
        }

        $this->fireEvent('request:invoked', compact('controller', 'action', 'return'));

        return $return;
    }

    /**
     * Dispatches a handle action taking into account the routing parameters
     *
     * @param \ManaPHP\Http\RouterInterface|\ManaPHP\Http\RouterContext $router
     *
     * @return mixed
     * @throws \ManaPHP\Http\Dispatcher\NotFoundControllerException
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

        $this->request->setParams($params);

        if ($area) {
            $area = str_contains($area, '_') ? Str::camelize($area) : ucfirst($area);
            $context->area = $area;
        }

        $controller = str_contains($controller, '_') ? Str::camelize($controller) : ucfirst($controller);
        $context->controller = $controller;

        $action = str_contains($action, '_') ? Str::variablize($action) : $action;
        $context->action = $action;

        $context->params = $params;

        if ($area) {
            $controllerClassName = "App\\Areas\\$area\\Controllers\\{$controller}Controller";
        } else {
            $controllerClassName = "App\\Controllers\\{$controller}Controller";
        }

        if (!class_exists($controllerClassName)) {
            throw new NotFoundControllerException(['`%s` class cannot be loaded', $controllerClassName]);
        }

        /** @var \ManaPHP\Controller $controllerInstance */
        $controllerInstance = $this->getShared($controllerClassName);
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
