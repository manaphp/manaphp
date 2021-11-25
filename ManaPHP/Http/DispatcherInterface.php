<?php

namespace ManaPHP\Http;

interface DispatcherInterface
{
    /**
     * @return string
     */
    public function getArea();

    /**
     * @return string
     */
    public function getController();

    /**
     * @return string
     */
    public function getAction();

    /**
     * @return array
     */
    public function getParams();

    /**
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
     * @return \ManaPHP\Http\Controller
     */
    public function getControllerInstance();

    /**
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