<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb\Event;

use ManaPHP\Mongodb\Connection;

class MongodbConnect
{
    public function __construct(
        public Connection $connection,
        public string $uri,
    ) {

    }
}