<?php
namespace ManaPHP\Logger\Appender;

use ManaPHP\Component;
use ManaPHP\Logger\AppenderInterface;

/**
 * Class ManaPHP\Logger\Appender\Memory
 *
 * @package logger
 */
class Memory extends Component implements AppenderInterface
{
    /**
     * @var \ManaPHP\Logger\Log[]
     */
    protected $_logs = [];

    /**
     * @param \ManaPHP\Logger\Log $log
     *
     * @return void
     */
    public function append($log)
    {
        $this->_logs[] = $log;
    }

    /**
     * @return \ManaPHP\Logger\Log[]
     */
    public function getLogs()
    {
        return $this->_logs;
    }
}