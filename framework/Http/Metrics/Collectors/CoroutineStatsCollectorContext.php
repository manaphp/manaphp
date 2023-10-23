<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use Swoole\Coroutine\Channel;

class CoroutineStatsCollectorContext
{
    public Channel $channel;

    public array $stats = [];
}