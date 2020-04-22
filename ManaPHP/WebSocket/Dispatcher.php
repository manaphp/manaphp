<?php

namespace ManaPHP\WebSocket;

/**
 * Class Dispatcher
 *
 * @package ManaPHP\WebSocket
 */
class Dispatcher extends \ManaPHP\Dispatcher
{
    /**
     * @param \ManaPHP\Controller $controller
     * @param string              $action
     *
     * @return mixed
     */
    public function invokeAction($controller, $action)
    {
        if (!$controller->isInvokable($action)) {
            return null;
        }

        return $controller->invoke($action);
    }
}