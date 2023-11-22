<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use Swoole\Http\Server;

#[Verbosity(Verbosity::LOW)]
class ServerReady
{
    public function __construct(public ?Server $server = null)
    {

    }
}