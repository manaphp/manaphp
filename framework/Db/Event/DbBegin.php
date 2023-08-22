<?php
declare(strict_types=1);

namespace ManaPHP\Db\Event;

use ManaPHP\Db\DbInterface;

class DbBegin
{
    public function __construct(
        public DbInterface $db,
    ) {

    }
}