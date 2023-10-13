<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Mongodb\MongodbInterface;

#[Verbosity(Verbosity::LOW)]
class MongodbCommanding extends AbstractEvent
{
    public function __construct(
        public MongodbInterface $mongodb,
        public string $db,
        public array $command,
    ) {

    }
}