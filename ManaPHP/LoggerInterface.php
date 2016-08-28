<?php
namespace ManaPHP;

interface LoggerInterface
{
    /**
     * Filters the logs sent to the handlers to be greater or equals than a specific level
     *
     * @param string $level
     *
     * @return static
     */
    public function setLevel($level);

    /**
     * Returns the current log level
     *
     * @return string
     */
    public function getLevel();

    /**
     * @return array
     */
    public function getLevels();

    /**
     * Sends/Writes a debug message to the log
     *
     * @param string $message
     * @param array  $context
     *
     * @return static
     */
    public function debug($message, $context = []);

    /**
     * Sends/Writes an info message to the log
     *
     * @param string $message
     * @param array  $context
     *
     * @return static
     */
    public function info($message, $context = []);

    /**
     * Sends/Writes a warning message to the log
     *
     * @param string $message
     * @param array  $context
     *
     * @return static
     */
    public function warning($message, $context = []);

    /**
     * Sends/Writes an error message to the log
     *
     * @param string $message
     * @param array  $context
     *
     * @return static
     */
    public function error($message, $context = []);

    /**
     * Sends/Writes a critical message to the log
     *
     * @param string $message
     * @param array  $context
     *
     * @return static
     */
    public function fatal($message, $context = []);
}