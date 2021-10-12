<?php

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Logging\LoggerContext;

class MemoryContext extends LoggerContext
{
    /**
     * @var \ManaPHP\Logging\Logger\Log[]
     */
    public $logs = [];
}