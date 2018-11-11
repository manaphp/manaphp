<?php

namespace ManaPHP\Mvc;

/**
 * Interface ManaPHP\Mvc\DispatcherInterface
 *
 * @package dispatcher
 */
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
     * @param  string|int $param
     * @param  string     $rule
     *
     * @return mixed
     */
    public function getParam($param, $rule = null);

    /**
     * @param string $param
     *
     * @return bool
     */
    public function hasParam($param);

    /**
     * @return \ManaPHP\Mvc\Controller
     */
    public function getControllerInstance();

    /**
     * Returns value returned by the latest dispatched action
     *
     * @return mixed
     */
    public function getReturnedValue();

    /**
     * @param string $controller
     *
     * @return string
     */
    public function getControllerClassName($controller = null);

    /**
     * Dispatches a handle action taking into account the routing parameters
     *
     * @param \ManaPHP\RouterInterface $router
     *
     * @return void
     */
    public function dispatch($router);

    /**
     * @param string $glue
     *
     * @return string
     */
    public function getMCA($glue = '/');
}