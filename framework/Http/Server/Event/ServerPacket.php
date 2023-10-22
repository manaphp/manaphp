<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use Swoole\Http\Server;

#[Verbosity(Verbosity::MEDIUM)]
class ServerPacket
{
    public function __construct(public Server $server, public string $data, public array $client)
    {

    }
}