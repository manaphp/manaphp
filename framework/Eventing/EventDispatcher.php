<?php
declare(strict_types=1);

namespace ManaPHP\Eventing;

use ManaPHP\Di\Attribute\Inject;

class EventDispatcher implements EventDispatcherInterface
{
    #[Inject] protected ListenerProviderInterface $listenerProvider;

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