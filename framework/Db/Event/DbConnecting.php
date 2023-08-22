<?php
declare(strict_types=1);

namespace ManaPHP\Db\Event;

use ManaPHP\Db\ConnectionInterface;

class DbConnecting
{
    public function __construct(
        public ConnectionInterface $connection,
        public string $dsn,
        public string $uri,
    ) {

    }
}