<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Mongodb\MongodbInterface;

#[Verbosity(Verbosity::HIGH)]
class MongodbUpdated extends AbstractEvent
{
    public function __construct(
        public MongodbInterface $mongodb,
        public string $namespace,
        public array $document,
        public array $filter,
        public int $count,
    ) {

    }
}