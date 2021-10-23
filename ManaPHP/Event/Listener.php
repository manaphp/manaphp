<?php

namespace ManaPHP\Event;

use ManaPHP\Component;
use ManaPHP\Logging\Logger\LogCategorizable;

abstract class Listener extends Component implements LogCategorizable, ListenInterface
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Listener');
    }
}