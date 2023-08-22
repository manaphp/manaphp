<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Chatting\Server\Event;

use ManaPHP\Ws\Chatting\ServerInterface;

class UserLeave
{
    public function __construct(
        public ServerInterface $server,
        public int $fd,
        public string|int $id,
        public string $name,
        public string $room,
    ) {

    }
}