<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb\Event;

use ManaPHP\Mongodb\MongodbInterface;

class MongodbCommanded extends AbstractEvent
{
    public function __construct(
        public MongodbInterface $mongodb,
        public string $db,
        public array $command,
        public array $result,
        public int $count,
        public float $elapsed,
    ) {

    }
}