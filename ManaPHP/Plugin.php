<?php

namespace ManaPHP;

use ManaPHP\Logging\Logger\LogCategorizable;

class Plugin extends Component implements LogCategorizable
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Plugin');
    }
}