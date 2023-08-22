<?php
declare(strict_types=1);

namespace ManaPHP\Cli\Event;

use ManaPHP\Cli\HandlerInterface;

class CliInvoking
{
    public function __construct(
        public HandlerInterface $handler,
        public object $command,
        public string $method,
        public string $action,
    ) {

    }
}