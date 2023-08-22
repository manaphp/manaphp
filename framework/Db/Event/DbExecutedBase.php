<?php
declare(strict_types=1);

namespace ManaPHP\Db\Event;

use ManaPHP\Db\DbInterface;

class DbExecutedBase
{
    public function __construct(
        public DbInterface $db,
        public string $type,
        public string $sql,
        public array $bind,
        public int $count,
        public $elapsed,
    ) {

    }
}