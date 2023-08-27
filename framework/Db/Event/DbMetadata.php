<?php
declare(strict_types=1);

namespace ManaPHP\Db\Event;

use ManaPHP\Db\DbInterface;

class DbMetadata extends AbstractEvent
{
    public function __construct(
        public DbInterface $db,
        public string $table,
        public array $meta,
        public float $elapsed,
    ) {

    }
}