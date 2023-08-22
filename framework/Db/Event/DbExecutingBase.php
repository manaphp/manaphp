<?php
declare(strict_types=1);

namespace ManaPHP\Db\Event;

use ManaPHP\Db\DbInterface;

class DbExecutingBase
{
    public function __construct(
        public DbInterface $db,
        public string $type,
        public string $sql,
        public array $bind
    ) {

    }
}