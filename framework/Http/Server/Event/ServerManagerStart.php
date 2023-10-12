<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Http\ServerInterface;
use Swoole\Http\Server;

#[Verbosity(Verbosity::LOW)]
class ServerManagerStart
{
    public function __construct(
        public ServerInterface $server,
        public Server $swoole,
    ) {

    }
}