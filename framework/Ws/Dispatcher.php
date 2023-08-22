<?php
declare(strict_types=1);

namespace ManaPHP\Ws;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Eventing\EventSubscriberInterface;
use ManaPHP\Http\Controller;
use ManaPHP\Ws\Server\Event\ServerStop;

class Dispatcher extends \ManaPHP\Http\Dispatcher implements DispatcherInterface
{
    #[Inject] protected EventSubscriberInterface $eventSubscriber;

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
                $this->eventSubscriber->subscribe(ServerStop::class, [$controller, 'stopAction']);
            }
        }

        if (!method_exists($controller, $action . 'Action')) {
            return null;
        }

        return $controller->invoke($action);
    }
}