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
    protected ?array $items = [];
    protected null|SwooleChannel|SplQueue $queue = null;

    public function __construct(int $capacity)
    {
        $this->capacity = $capacity;
        $this->length = 0;
    }

    public function push(mixed $data): void
    {
        if ($this->length + 1 > $this->capacity) {
            throw new MisuseException('channel is full');
        }

        $this->length++;

        if ($this->items === null) {
            $this->queue->push($data);
        } else {
            $this->items[] = $data;
        }
    }

    public function pop(?float $timeout = null): mixed
    {
        if ($this->queue === null) {
            $this->queue = MANAPHP_COROUTINE_ENABLED ? new SwooleChannel($this->capacity) : new SplQueue();
            foreach ($this->items as $item) {
                $this->queue->push($item);
            }
            $this->items = null;
        }

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