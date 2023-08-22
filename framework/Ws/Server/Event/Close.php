<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Server\Event;

class Close
{
    public function __construct(
        public int $fd,
    ) {

    }
}