<?php
declare(strict_types=1);

namespace ManaPHP\Cli\Event;

use ManaPHP\Cli\DispatcherInterface;

class CliInvoked
{
    public function __construct(
        public DispatcherInterface $dispatcher,
        public object $command,
        public string $method,
        public string $action,
        public mixed $return,
    ) {

    }
}