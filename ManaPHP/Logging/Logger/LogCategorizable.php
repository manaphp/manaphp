<?php

namespace ManaPHP\Logging\Logger;

interface LogCategorizable
{
    /**
     * @return string
     */
    public function categorizeLog();
}