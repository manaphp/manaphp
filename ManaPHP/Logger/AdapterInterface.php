<?php
namespace ManaPHP\Logger;

/**
 * Interface ManaPHP\Logger\AdapterInterface
 *
 * @package logger
 */
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