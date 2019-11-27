<?php
namespace ManaPHP\WebSocket;

/**
 * Class Dispatcher
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
        $actionMethod = $action . 'Action';

        if (!method_exists($controller, $actionMethod)) {
            return null;
        }

        return $this->invoker->invoke($controller, $actionMethod);
    }
}