<?php
namespace ManaPHP\Logger\Appender;

use ManaPHP\Component;
use ManaPHP\Logger\AppenderInterface;

class _MemoryContext
{
    /**
     * @var \ManaPHP\Logger\Log[]
     */
    public $logs = [];
}

/**
 * Class ManaPHP\Logger\Appender\Memory
 *
 * @package logger
 */
class Memory extends Component implements AppenderInterface
{
    public function __construct()
    {
        $this->_context = new _MemoryContext();
    }

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