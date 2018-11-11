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
     * Gets last dispatched action name
     *
     * @return string
     */
    public function getActionName();

    /**
     * @param string $actionName
     *
     * @return static
     */
    public function setActionName($actionName);

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
    public function getController();

    /**
     * Returns value returned by the latest dispatched action
     *
     * @return mixed
     */
    public function getReturnedValue();

    /**
     * @param string $controllerName
     *
     * @return string
     */
    public function getControllerClassName($controllerName = null);

    /**
     * Dispatches a handle action taking into account the routing parameters
     *
     * @param \ManaPHP\RouterInterface $router
     *
     * @return void
     */
    public function dispatch($router);

    /**
     * Gets last dispatched controller name
     *
     * @return string
     */
    public function getControllerName();

    /**
     * @param string $controllerName
     *
     * @return static
     */
    public function setControllerName($controllerName);

    /**
     * @param string $glue
     *
     * @return string
     */
    public function getMCA($glue = '/');
}