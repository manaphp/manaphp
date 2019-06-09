<?php
namespace ManaPHP\Swoole;

use ManaPHP\Component;
use ManaPHP\Logger\LogCategorizable;

abstract class Process extends Component implements ProcessInterface, LogCategorizable
{
    /**
     * @var \Swoole\Process
     */
    protected $_process;

    public function __construct()
    {
        $this->_process = new \Swoole\Process([$this, 'run']);
    }

    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Process');
    }
}