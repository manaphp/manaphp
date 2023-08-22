<?php
declare(strict_types=1);

namespace ManaPHP\Db\Event;

use ManaPHP\Db\DbInterface;

class DbQuerying
{
    public function __construct(
        public DbInterface $db,
        public string $sql,
        public array $bind,
    ) {

    }
}