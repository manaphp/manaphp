<?php
namespace ManaPHP\Logger;

interface AdapterInterface
{
    /**
     * @param string $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function log($level, $message, $context = []);
}