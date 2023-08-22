<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb\Event;

use ManaPHP\Mongodb\MongodbInterface;

class MongodbUpdating
{
    public function __construct(
        public MongodbInterface $mongodb,
        public string $namespace,
        public array $document,
        public array $filter,
    ) {

    }
}