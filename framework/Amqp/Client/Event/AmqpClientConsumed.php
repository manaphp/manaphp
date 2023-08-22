<?php
declare(strict_types=1);

namespace ManaPHP\Amqp\Client\Event;

use ManaPHP\Amqp\ClientInterface;
use ManaPHP\Amqp\MessageInterface;
use ManaPHP\Amqp\Queue;

class AmqpClientConsumed
{
    public function __construct(
        public ClientInterface $client,
        public string|Queue $queue,
        public MessageInterface $message,
        public mixed $return,
    ) {

    }
}