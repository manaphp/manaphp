<?php
namespace ManaPHP;

interface ActionInvokerInterface
{
    /**
     * @return \ManaPHP\Controller
     */
    public function getController();

    /**
     * @return string
     */
    public function getAction();

    /**
     * @param \ManaPHP\Controller $controller
     * @param string              $action
     * @param array               $params
     *
     * @return mixed
     */
    public function invoke($controller, $action, $params);
}