<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Event;

use ManaPHP\Logging\Logger\Log;
use ManaPHP\Logging\LoggerInterface;

class LoggerLog
{
    public function __construct(
        public LoggerInterface $logger,
        public string $level,
        public mixed $message,
        public ?string $category,
        public Log $log,
    ) {

    }
}