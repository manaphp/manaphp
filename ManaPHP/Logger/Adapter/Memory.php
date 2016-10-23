<?php
namespace ManaPHP\Logger\Adapter;

use ManaPHP\Component;
use ManaPHP\Logger\AdapterInterface;

/**
 * Class ManaPHP\Logger\Adapter\Memory
 *
 * @package ManaPHP\Logger\Adapter
 */
class Memory extends Component implements AdapterInterface
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
    public function log($level, $message, $context = [])
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