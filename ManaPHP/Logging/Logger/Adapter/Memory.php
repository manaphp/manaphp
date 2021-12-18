<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Logging\AbstractLogger;

/**
 * @property-read \ManaPHP\Logging\Logger\Adapter\MemoryContext $context
 */
class Memory extends AbstractLogger
{
    public function append(array $logs): void
    {
        $context = $this->context;

        $context->logs = array_merge($context->logs, $logs);
    }

    public function getLogs(): array
    {
        return $this->context->logs;
    }
}