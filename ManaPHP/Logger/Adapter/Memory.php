<?php
namespace ManaPHP\Logger\Adapter;

use ManaPHP\Logger;

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
 * @property \ManaPHP\Logger\Adapter\MemoryContext $_context
 */
class Memory extends Logger
{
    /**
     * @param \ManaPHP\Logger\Log $log
     *
     * @return void
     */
    public function append($log)
    {
        $context = $this->_context;

        $context->logs[] = $log;
    }

    /**
     * @return \ManaPHP\Logger\Log[]
     */
    public function getLogs()
    {
        return $this->_context->logs;
    }
}