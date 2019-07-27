<?php
namespace ManaPHP;

use ManaPHP\Logger\LogCategorizable;

abstract class Cron extends Component implements CronInterface, LogCategorizable
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Cron');
    }
}