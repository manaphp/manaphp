<?php

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Logging\Logger;
use ManaPHP\Logging\LoggerContext;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class MemoryContext extends LoggerContext
{
    /**
     * @var \ManaPHP\Logging\Logger\Log[]
     */
    public $logs = [];
}

/**
 * @property-read \ManaPHP\Logging\Logger\Adapter\MemoryContext $context
 */
class Memory extends Logger
{
    /**
     * @param \ManaPHP\Logging\Logger\Log[] $logs
     *
     * @return void
     */
    public function append($logs)
    {
        $context = $this->context;

        $context->logs = array_merge($context->logs, $logs);
    }

    /**
     * @return \ManaPHP\Logging\Logger\Log[]
     */
    public function getLogs()
    {
        return $this->context->logs;
    }
}