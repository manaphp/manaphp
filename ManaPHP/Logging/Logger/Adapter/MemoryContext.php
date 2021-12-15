<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Logging\AbstractLoggerContext;

class MemoryContext extends AbstractLoggerContext
{
    /**
     * @var \ManaPHP\Logging\Logger\Log[]
     */
    public array $logs = [];
}