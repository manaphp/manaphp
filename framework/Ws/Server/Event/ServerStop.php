<?php

namespace ManaPHP\Ws\Server\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Ws\ServerInterface;
use Swoole\Http\Server;

#[Verbosity(Verbosity::LOW)]
class ServerStop
{
    public function __construct(
        public ServerInterface $server,
        public Server $swoole,
        public int $worker_id,
    ) {

    }
}