<?php

namespace ManaPHP\Event;

use ManaPHP\Component;
use ManaPHP\Logging\Logger\LogCategorizable;

class Listener extends Component implements LogCategorizable
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Listener');
    }
}