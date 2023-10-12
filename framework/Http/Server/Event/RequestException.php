<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use Throwable;

#[Verbosity(Verbosity::LOW)]
class RequestException
{
    public function __construct(
        public Throwable $exception
    ) {

    }
}