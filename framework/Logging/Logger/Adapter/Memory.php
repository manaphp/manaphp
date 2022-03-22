<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Logging\AbstractLogger;
use ManaPHP\Logging\Logger\Log;

/**
 * @property-read \ManaPHP\Logging\Logger\Adapter\MemoryContext $context
 */
class Memory extends AbstractLogger
{
    public function append(Log $log): void
    {
        $context = $this->context;

        $context->logs[] = $log;
    }

    public function getLogs(): array
    {
        return $this->context->logs;
    }
}