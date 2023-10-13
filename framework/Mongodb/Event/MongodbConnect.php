<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Mongodb\Connection;

#[Verbosity(Verbosity::MEDIUM)]
class MongodbConnect extends AbstractEvent
{
    public function __construct(
        public Connection $connection,
        public string $uri,
    ) {

    }
}