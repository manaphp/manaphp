<?php
namespace ManaPHP;

interface ActionInvokerInterface
{
    /**
     * @param \ManaPHP\Mvc\ControllerInterface $controller
     * @param string                           $action
     * @param array                            $params
     *
     * @return mixed
     */
    public function invoke($controller, $action, $params);
}