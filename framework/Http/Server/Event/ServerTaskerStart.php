<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use Swoole\Http\Server;

#[Verbosity(Verbosity::HIGH)]
class ServerTaskerStart
{
    public function __construct(public Server $server, public int $worker_id, public int $tasker_id)
    {

    }
}