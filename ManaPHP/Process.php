<?php

namespace ManaPHP;

use ManaPHP\Logger\LogCategorizable;

abstract class Process extends Component implements ProcessInterface, LogCategorizable
{
    /**
     * @var \Swoole\Process
     */
    protected $_process;

    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Process');
    }
}