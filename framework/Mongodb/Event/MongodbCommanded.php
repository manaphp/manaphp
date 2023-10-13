<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Mongodb\MongodbInterface;

#[Verbosity(Verbosity::HIGH)]
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