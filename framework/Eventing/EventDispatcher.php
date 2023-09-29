<?php
declare(strict_types=1);

namespace ManaPHP\Eventing;

use ManaPHP\Di\Attribute\Autowired;

class EventDispatcher implements EventDispatcherInterface
{
    #[Autowired] protected ListenerProviderInterface $listenerProvider;

    public function dispatch(object $event)
    {
        foreach ($this->listenerProvider->getListenersForPeeker() as $listeners) {
            foreach ($listeners as $listener) {
                $listener($event);
            }
        }

        foreach ($this->listenerProvider->getListenersForEvent($event) as $listeners) {
            foreach ($listeners as $listener) {
                $listener($event);
            }
        }
    }
}