<?php

namespace ManaPHP\Logger\Adapter;

use ManaPHP\Logger;

class Noop extends Logger
{
    public function append($logs)
    {
        null;
    }
}