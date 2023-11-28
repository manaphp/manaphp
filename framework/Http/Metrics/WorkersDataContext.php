<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics;

use Swoole\Coroutine\Channel;

class WorkersDataContext
{
    public array $data = [];

    public Channel $channel;
}