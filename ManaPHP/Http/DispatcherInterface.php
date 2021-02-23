<?php

namespace ManaPHP\Http;

interface DispatcherInterface
{
    /**
     * Gets last dispatched area name
     *
     * @return string
     */
    public function getArea();

    /**
     * @param string $area
     *
     * @return static
     */
    public function setArea($area);

    /**
     * Gets last dispatched controller name
     *
     * @return string
     */
    public function getController();

    /**
     * @param string $controller
     *
     * @return static
     */
    public function setController($controller);

    /**
     * Gets last dispatched action name
     *
     * @return string
     */
    public function getAction();

    /**
     * @param string $action
     *
     * @return static
     */
    public function setAction($action);

    /**
     * @param array $params
     * @param bool  $merge
     *
     * @return static
     */
    public function setParams($params, $merge = true);

    /**
     * Gets action params
     *
     * @return array
     */
    public function getParams();

    /**
     * Gets a param by its name or numeric index
     *
     * @param string|int $name
     * @param mixed      $default
     *
     * @return mixed
     */
    public function getParam($name, $default = null);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasParam($name);

    /**
     * @return string
     */
    public function getPath();

    /**
     * @return \ManaPHP\Controller
     */
    public function getControllerInstance();

    /**
     * Dispatches a handle action taking into account the routing parameters
     *
     * @param string $area
     * @param string $controller
     * @param string $action
     * @param array  $params
     *
     * @return mixed
     * @throws \ManaPHP\Http\Dispatcher\NotFoundControllerException
     * @throws \ManaPHP\Exception\AbortException
     */
    public function dispatch($area, $controller, $action, $params);

    /**
     * @return bool
     */
    public function isInvoking();
}