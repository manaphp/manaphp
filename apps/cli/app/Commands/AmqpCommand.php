<?php

namespace App\Commands;

use ManaPHP\Amqp\Engine\Php\Message;
use ManaPHP\Amqp\Exchange;
use ManaPHP\Amqp\Queue;

/**
 * @property-read \ManaPHP\Amqp\ClientInterface $amqpClient
 */
class AmqpCommand extends Command
{
    public function publishAction()
    {
        $queue = new Queue('abc');
        $this->amqpClient->basicPublish('', $queue, date('Y-m-d H:i:s'));
    }

    public function consumeAction()
    {
        $this->amqpClient->basicConsume(
            new Queue('abc'), function (Message $message) {
            $this->console->writeLn($message->getBody());
        }
        );

        $this->amqpClient->startConsume();
    }
}