<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Http\DispatcherInterface;

#[Verbosity(Verbosity::HIGH)]
class RequestInvoked
{
    public function __construct(
        public DispatcherInterface $dispatcher,
        public object $controller,
        public string $action,
        public mixed $return,
    ) {
    }
}