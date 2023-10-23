<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Http\Server\Event\ServerWorkerStart;
use Swoole\Server;

abstract class AbstractCollector implements CollectorInterface
{
    #[Autowired] protected FormatterInterface $formatter;

    protected Server $server;

    public function onServerWorkerStart(#[Event] ServerWorkerStart $event)
    {
        $this->server = $event->server;
    }
}