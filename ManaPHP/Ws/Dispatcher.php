<?php

namespace ManaPHP\Ws;

/**
 * Class Dispatcher
 *
 * @package ManaPHP\Ws
 */
class Dispatcher extends \ManaPHP\Http\Dispatcher implements DispatcherInterface
{
    /**
     * @var array
     */
    protected $_controllers;

    /**
     * @param \ManaPHP\Controller $controller
     * @param string              $action
     *
     * @return mixed
     */
    public function invokeAction($controller, $action)
    {
        $controller_oid = $controller->_object_id;
        if (!isset($this->_controllers[$controller_oid])) {
            $this->_controllers[$controller_oid] = true;

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