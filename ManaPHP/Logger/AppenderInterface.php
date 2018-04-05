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
     * @param \ManaPHP\Logger\Log $log
     *
     * @return void
     */
    public function append($log);
}