<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View;

use ManaPHP\Logging\Logger\LogCategorizable;

abstract class Widget implements WidgetInterface, LogCategorizable
{
    public function categorizeLog(): string
    {
        return basename(str_replace('\\', '.', static::class), 'Widget');
    }
}