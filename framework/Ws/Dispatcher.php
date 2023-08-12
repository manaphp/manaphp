<?php
declare(strict_types=1);

namespace ManaPHP\Ws;

use ManaPHP\Event\EventTrait;
use ManaPHP\Http\Controller;

class Dispatcher extends \ManaPHP\Http\Dispatcher implements DispatcherInterface
{
    use EventTrait;

    protected array $controllers;

    public function invokeAction(Controller $controller, string $action): mixed
    {
        $controller_oid = spl_object_id($controller);
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