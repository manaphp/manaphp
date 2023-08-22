<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Http\DispatcherInterface;

class RequestDispatchBase
{
    public function __construct(
        public DispatcherInterface $dispatcher,
        public object $controller,
        public string $action,
    ) {

    }
}