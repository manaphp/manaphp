<?php
declare(strict_types=1);

namespace ManaPHP\Eventing;

use Psr\EventDispatcher\ListenerProviderInterface as PsrListenerProviderInterface;

interface ListenerProviderInterface extends PsrListenerProviderInterface
{
    public function on(string $event, callable $handler, int $priority = 0);

    public function add(string|object $listener);

    public function getListenersForEvent(object $event): iterable;

    public function getListenersForPeeker(): iterable;
}