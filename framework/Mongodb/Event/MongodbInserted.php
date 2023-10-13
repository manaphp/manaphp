<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Mongodb\MongodbInterface;

#[Verbosity(Verbosity::HIGH)]
class MongodbInserted extends AbstractEvent
{
    public function __construct(
        public MongodbInterface $mongodb,
        public int $count,
        public string $namespace,
        public array $document
    ) {

    }
}