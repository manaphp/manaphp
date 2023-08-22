<?php
declare(strict_types=1);

namespace ManaPHP\Messaging;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Messaging\Queue\Event\QueueDelete;
use ManaPHP\Messaging\Queue\Event\QueuePop;
use ManaPHP\Messaging\Queue\Event\QueuePush;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class AbstractQueue implements QueueInterface
{
    #[Inject] protected EventDispatcherInterface $eventDispatcher;

    abstract public function do_push(string $topic, string $body, int $priority = self::PRIORITY_NORMAL): void;

    public function push(string $topic, string $body, int $priority = self::PRIORITY_NORMAL): void
    {
        $this->eventDispatcher->dispatch(new QueuePush($this, $topic, $body, $priority));

        $this->do_push($topic, $body, $priority);
    }

    /**
     * @param string $topic
     * @param int    $timeout
     *
     * @return ?string
     */
    abstract public function do_pop(string $topic, int $timeout = PHP_INT_MAX): ?string;

    public function pop(string $topic, int $timeout = PHP_INT_MAX): ?string
    {
        if (($msg = $this->do_pop($topic, $timeout)) !== null) {
            $this->eventDispatcher->dispatch(new QueuePop($this, $topic, $msg));
        }

        return $msg;
    }

    abstract public function do_delete(string $topic): void;

    public function delete(string $topic): void
    {
        $this->eventDispatcher->dispatch(new QueueDelete($this, $topic));
        $this->do_delete($topic);
    }

    abstract public function do_length(string $topic, ?int $priority = null): int;

    public function length(string $topic, ?int $priority = null): int
    {
        return $this->do_length($topic, $priority);
    }
}