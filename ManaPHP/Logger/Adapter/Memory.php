<?php

namespace ManaPHP\Logger\Adapter;

use ManaPHP\Logger;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class MemoryContext
{
    /**
     * @var \ManaPHP\Logger\Log[]
     */
    public $logs = [];
}

/**
 * Class ManaPHP\Logger\Adapter\Memory
 *
 * @package logger
 * @property-read \ManaPHP\Logger\Adapter\MemoryContext $_context
 */
class Memory extends Logger
{
    /**
     * @param \ManaPHP\Logger\Log[] $logs
     *
     * @return void
     */
    public function append($logs)
    {
        $context = $this->_context;

        $context->logs = array_merge($context->logs, $logs);
    }

    /**
     * @return \ManaPHP\Logger\Log[]
     */
    public function getLogs()
    {
        return $this->_context->logs;
    }
}