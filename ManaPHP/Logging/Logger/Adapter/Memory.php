<?php

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Logging\Logger;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class MemoryContext
{
    /**
     * @var \ManaPHP\Logging\Logger\Log[]
     */
    public $logs = [];
}

/**
 * Class ManaPHP\Logging\Logger\Adapter\Memory
 *
 * @package logger
 * @property-read \ManaPHP\Logging\Logger\Adapter\MemoryContext $_context
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
        $context = $this->_context;

        $context->logs = array_merge($context->logs, $logs);
    }

    /**
     * @return \ManaPHP\Logging\Logger\Log[]
     */
    public function getLogs()
    {
        return $this->_context->logs;
    }
}