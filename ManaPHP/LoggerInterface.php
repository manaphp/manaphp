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
     * @param string $category
     */
    public function setCategory($category);

    /**
     * @return array
     */
    public function getLevels();

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