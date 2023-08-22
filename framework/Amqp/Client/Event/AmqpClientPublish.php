<?php
declare(strict_types=1);

namespace ManaPHP\Amqp\Client\Event;

use ManaPHP\Amqp\ClientInterface;
use ManaPHP\Amqp\Exchange;
use ManaPHP\Amqp\Queue;

class AmqpClientPublish
{
    public function __construct(
        public ClientInterface $client,
        public string|Exchange $exchange,
        public string|Queue $routing_key,
        public string|array $body,
        public array $properties,
        public bool $mandatory,
    ) {

    }
}