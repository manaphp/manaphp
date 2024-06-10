<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

use ManaPHP\Logging\Logger\Log;

interface AppenderInterface
{
    public function append(Log $log): void;
}