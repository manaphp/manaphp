<?php

namespace ManaPHP\Ws;

class Dispatcher extends \ManaPHP\Http\Dispatcher implements DispatcherInterface
{
    /**
     * @var array
     */
    protected $controllers;

    /**
     * @param \ManaPHP\Ws\Controller $controller
     * @param string                 $action
     *
     * @return mixed
     */
    public function invokeAction($controller, $action)
    {
        $controller_oid = $controller->object_id;
        if (!isset($this->controllers[$controller_oid])) {
            $this->controllers[$controller_oid] = true;

            if (method_exists($controller, 'startAction')) {
                $controller->startAction();
            }

            if (method_exists($controller, 'stopAction')) {
                $controller->attachEvent('wsServer:stop', [$controller, 'stopAction']);
            }
        }

        if (!method_exists($controller, $action . 'Action')) {
            return null;
        }

        return $controller->invoke($action);
    }
}