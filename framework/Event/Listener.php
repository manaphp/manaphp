<?php
declare(strict_types=1);

namespace ManaPHP\Event;

use ManaPHP\Logging\Logger\LogCategorizable;

abstract class Listener implements LogCategorizable, ListenInterface
{
    use EventTrait;

    public function categorizeLog(): string
    {
        return basename(str_replace('\\', '.', static::class), 'Listener');
    }
}