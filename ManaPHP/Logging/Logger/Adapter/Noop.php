<?php

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Logging\AbstractLogger;

class Noop extends AbstractLogger
{
    public function append($logs)
    {
        null;
    }
}