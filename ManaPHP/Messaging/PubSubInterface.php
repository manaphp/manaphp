<?php
declare(strict_types=1);

namespace ManaPHP\Messaging;

interface PubSubInterface
{
    public function subscribe(array $channels, callable $callback): void;

    public function psubscribe(array $patterns, callable $callback): void;

    public function publish(string $channel, string $message): int;

    public function unsubscribe(?array $channels = null): void;

    public function punsubscribe(?array $patterns = null): void;
}
