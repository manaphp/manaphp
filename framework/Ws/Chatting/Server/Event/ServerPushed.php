<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Chatting\Server;

use ManaPHP\Ws\Chatting\ServerInterface;

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