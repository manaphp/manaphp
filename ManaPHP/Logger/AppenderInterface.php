<?php
namespace ManaPHP\Logger;

/**
 * Interface ManaPHP\Logger\AppenderInterface
 *
 * @package logger
 */
interface AppenderInterface
{
    /**
     * @param array $logEvent
     *
     * @return void
     */
    public function append($logEvent);
}