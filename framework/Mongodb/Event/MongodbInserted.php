<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb\Event;

use ManaPHP\Mongodb\MongodbInterface;

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