<?php

namespace ManaPHP\Logger;

interface LogCategorizable
{
    /**
     * @return string
     */
    public function categorizeLog();
}