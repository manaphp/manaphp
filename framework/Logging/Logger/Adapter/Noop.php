<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Logging\AbstractLogger;
use ManaPHP\Logging\Logger\Log;

class Noop extends AbstractLogger
{
    public function append(Log $log): void
    {
        SuppressWarnings::noop();
    }
}