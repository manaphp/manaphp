<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Server\Event;

class Open
{
    public function __construct(
        public int $fd,
    ) {

    }
}