<?php
namespace ManaPHP\Logger;

/**
 * Interface ManaPHP\Logger\AdapterInterface
 *
 * @package ManaPHP\Logger
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