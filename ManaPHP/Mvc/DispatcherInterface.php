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
     * Gets the module where the controller class is
     *
     * @return string
     */
    public function getModuleName();

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
     * Dispatches a handle action taking into account the routing parameters
     *
     * @param string $module
     * @param string $controller
     * @param string $action
     * @param array  $params
     *
     * @return false|\ManaPHP\Mvc\ControllerInterface
     */
    public function dispatch($module, $controller, $action, $params = null);

    /**
     * Forwards the execution flow to another controller/action
     *
     * @param string $forward
     * @param array  $params
     */
    public function forward($forward, $params = []);

    /**
     * Check if the current executed action was forwarded by another one
     *
     * @return bool
     */
    public function wasForwarded();

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
     * Returns the previous controller in the dispatcher
     *
     * @return string
     */
    public function getPreviousControllerName();

    /**
     * Returns the previous action in the dispatcher
     *
     * @return string
     */
    public function getPreviousActionName();
}