<?php
declare(strict_types=1);

namespace ManaPHP\Event;

use ManaPHP\Di\Attribute\Inject;

trait EventTrait
{
    #[Inject]
    protected ManagerInterface $eventManager;

    protected function attachEvent(string $event, callable $handler, int $priority = 0): static
    {
        $this->eventManager->attachEvent($event, $handler, $priority);

        return $this;
    }

    protected function fireEvent(string $event, mixed $data = null): EventArgs
    {
        return $this->eventManager->fireEvent($event, $data, $this);
    }
}