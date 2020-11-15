<?php

namespace ManaPHP\Debugging;

interface DataDumpInterface
{
    /**
     * @param string|array $message
     *
     * @return void
     */
    public function output($message);
}