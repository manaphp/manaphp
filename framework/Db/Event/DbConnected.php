<?php
declare(strict_types=1);

namespace ManaPHP\Db\Event;

use ManaPHP\Db\ConnectionInterface;
use PDO;

class DbConnected
{
    public function __construct(
        public ConnectionInterface $connection,
        public string $dns,
        public string $uri,
        public ?PDO $pdo = null,
    ) {

    }
}