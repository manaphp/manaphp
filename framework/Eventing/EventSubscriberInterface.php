<?php
declare(strict_types=1);

namespace ManaPHP\Eventing;

interface EventSubscriberInterface
{
    public function subscribe(string $event, callable $handler, int $priority = 0);

    public function addListener(object $listener);
}