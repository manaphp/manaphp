<?php

namespace ManaPHP\Ws\Server\Event;

use ManaPHP\Ws\ServerInterface;
use Swoole\Http\Server;

class ServerStart
{
    public function __construct(
        public ServerInterface $server,
        public Server $swoole,
        public int $worker_id,
    ) {

    }
}