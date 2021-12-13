<?php

namespace ManaPHP\Mvc\View;

use ManaPHP\Component;
use ManaPHP\Logging\Logger\LogCategorizable;

abstract class Widget extends Component implements WidgetInterface, LogCategorizable
{
    public function categorizeLog(): string
    {
        return basename(str_replace('\\', '.', static::class), 'Widget');
    }
}