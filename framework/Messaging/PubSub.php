<?php
declare(strict_types=1);

namespace ManaPHP\Messaging;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Redis\RedisBrokerInterface;

class PubSub implements PubSubInterface
{
    #[Inject] protected RedisBrokerInterface $redisBroker;

    public function subscribe(array $channels, callable $callback): void
    {
        /** @noinspection PhpParamsInspection */
        $this->redisBroker->subscribe(
            $channels, static function ($redis, $channel, $msg) use ($callback) {
            $callback($channel, $msg);
        }
        );
    }

    public function psubscribe(array $patterns, callable $callback): void
    {
        /** @noinspection PhpParamsInspection */
        $this->redisBroker->psubscribe(
            $patterns, static function ($redis, $pattern, $channel, $msg) use ($callback) {
            $callback($channel, $msg);
        }
        );
    }

    public function publish(string $channel, string $message): int
    {
        return $this->redisBroker->publish($channel, $message);
    }

    public function unsubscribe(?array $channels = null): void
    {
        $this->redisBroker->unsubscribe($channels);
    }

    public function punsubscribe(?array $patterns = null): void
    {
        $this->redisBroker->punsubscribe($patterns);
    }
}
