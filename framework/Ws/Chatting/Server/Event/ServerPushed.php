<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Chatting\Server\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Ws\Chatting\ServerInterface;

#[Verbosity(Verbosity::HIGH)]
class ServerPushed
{
    public function __construct(
        public ServerInterface $server,
        public string $type,
        public array $receivers,
        public string $message,
    ) {

    }
}