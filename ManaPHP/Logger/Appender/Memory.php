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
     * @var array
     */
    protected $_logs = [];

    /**
     * @param array $logEvent
     *
     * @return void
     */
    public function append($logEvent)
    {
        $this->_logs[] = $logEvent;
    }

    /**
     * @return array
     */
    public function getLogs()
    {
        return $this->_logs;
    }
}