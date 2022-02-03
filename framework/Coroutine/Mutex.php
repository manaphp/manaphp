<?php
declare(strict_types=1);

namespace ManaPHP\Coroutine;

class Mutex
{
    protected Channel $channel;

    public function __construct()
    {
        $this->channel = new Channel(1);
        $this->channel->push('');
    }

    public function lock(): void
    {
        $this->channel->pop();
    }

    public function unlock(): void
    {
        $this->channel->push('');
    }

    public function isLocked(): bool
    {
        return $this->channel->isEmpty();
    }
}
