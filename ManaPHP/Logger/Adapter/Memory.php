<?php
namespace ManaPHP\Logger\Adapter;

use ManaPHP\Logger;

class Memory extends Logger
{
    /**
     * @var array
     */
    protected $_logs = [];

    /**
     * @param string $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function _log($level, $message, $context = [])
    {
        $this->_logs[] = ['level' => $level, 'message' => $message, 'context' => $context];
    }

    /**
     * @return array
     */
    public function getLogs()
    {
        return $this->_logs;
    }
}