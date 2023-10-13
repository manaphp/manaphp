<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Chatting\Client\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Ws\Chatting\ClientInterface;

#[Verbosity(Verbosity::MEDIUM)]
class ChatClientPush
{
    public function __construct(
        public ClientInterface $client,
        public string $type,
        public string $room,
        public string|array $receivers,
        public string|array $message,
    ) {

    }
}