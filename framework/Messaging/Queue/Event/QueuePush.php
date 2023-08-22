<?php
declare(strict_types=1);

namespace ManaPHP\Messaging\Queue\Event;

use ManaPHP\Messaging\QueueInterface;

class QueuePush
{
    public function __construct(
        public QueueInterface $queue,
        public string $topic,
        public string $body,
        public int $priority,
    ) {

    }
}