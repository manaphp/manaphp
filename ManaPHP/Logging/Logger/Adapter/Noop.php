<?php

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Logging\Logger;

class Noop extends Logger
{
    public function append($logs)
    {
        null;
    }
}