<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Logging\Logger\Log;
use Psr\Log\LoggerInterface;

#[Verbosity(Verbosity::HIGH)]
class LoggerLog
{
    public function __construct(
        public LoggerInterface $logger,
        public string $level,
        public mixed $message,
        public array $context,
        public Log $log,
    ) {

    }
}