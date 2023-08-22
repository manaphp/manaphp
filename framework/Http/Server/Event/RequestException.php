<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use Throwable;

class RequestException
{
    public function __construct(
        public Throwable $exception
    ) {

    }
}