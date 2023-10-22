<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use Swoole\Http\Server;

#[Verbosity(Verbosity::HIGH)]
class ServerConnect
{
    public function __construct(public Server $server, public int $fd, public int $reactor_id)
    {

    }
}