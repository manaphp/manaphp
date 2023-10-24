<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use Swoole\Coroutine\Channel;

class MemoryUsageCollectorContext
{
    public Channel $channel;

    public array $messages = [];
}