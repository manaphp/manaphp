<?php
declare(strict_types=1);

namespace ManaPHP\Coroutine;

use ManaPHP\Exception\MisuseException;
use SplQueue;
use Swoole\Coroutine\Channel as SwooleChannel;

class Channel
{
    protected int $capacity;
    protected int $length;

    /**
     * @var \Swoole\Coroutine\Channel|\SplQueue
     */
    protected mixed $queue;

    public function __construct(int $capacity)
    {
        $this->capacity = $capacity;
        $this->length = 0;
        $this->queue = MANAPHP_COROUTINE_ENABLED ? new SwooleChannel($capacity) : new SplQueue();
    }

    public function push(mixed $data): void
    {
        if ($this->length + 1 > $this->capacity) {
            throw new MisuseException('channel is full');
        }

        $this->length++;
        $this->queue->push($data);
    }

    public function pop(?float $timeout = null): mixed
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            $data = $timeout === null ? $this->queue->pop() : $this->queue->pop($timeout);
        } else {
            if ($this->length === 0) {
                throw new MisuseException('channel is empty');
            }

            $data = $this->queue->pop();
        }

        $this->length--;

        return $data;
    }

    public function isEmpty(): bool
    {
        return $this->length === 0;
    }

    public function isFull(): bool
    {
        return $this->length === $this->capacity;
    }

    public function length(): int
    {
        return $this->length;
    }

    public function capacity(): int
    {
        return $this->capacity;
    }
}