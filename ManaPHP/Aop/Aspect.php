<?php

namespace ManaPHP\Aop;

use ManaPHP\Component;
use ManaPHP\Logging\Logger\LogCategorizable;

/**
 * @property-read \ManaPHP\Aop\ManagerInterface $aopManager
 */
abstract class Aspect extends Component implements LogCategorizable
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', static::class), 'Aspect');
    }
}