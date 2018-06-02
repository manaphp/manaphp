<?php

namespace ManaPHP;

/**
 * Interface ManaPHP\LoggerInterface
 *
 * @package logger
 */
interface LoggerInterface
{
    /**
     * @param int|string $level
     *
     * @return static
     */
    public function setLevel($level);

    /**
     * @param string $category
     *
     * @return static
     */
    public function setCategory($category);

    /**
     * @return array
     */
    public function getLevels();

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasAppender($name);

    /**
     * @param int|string $name
     *
     * @return \ManaPHP\Logger\AppenderInterface
     */
    public function getAppender($name);

    /**
     * @param string|array|\ManaPHP\Logger\AppenderInterface $appender
     * @param string                                         $name
     *
     * @return static
     */
    public function addAppender($appender, $name = null);

    /**
     * Sends/Writes a trace message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return static
     */
    public function trace($message, $category = null);

    /**
     * Sends/Writes a debug message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return static
     */
    public function debug($message, $category = null);

    /**
     * Sends/Writes an info message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return static
     */
    public function info($message, $category = null);

    /**
     * Sends/Writes a warning message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return static
     */
    public function warn($message, $category = null);

    /**
     * Sends/Writes an error message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return static
     */
    public function error($message, $category = null);

    /**
     * Sends/Writes a critical message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return static
     */
    public function fatal($message, $category = null);
}