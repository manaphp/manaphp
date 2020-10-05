<?php

namespace ManaPHP;

interface DataDumpInterface
{
    /**
     * @param string|array $message
     *
     * @return void
     */
    public function output($message);
}