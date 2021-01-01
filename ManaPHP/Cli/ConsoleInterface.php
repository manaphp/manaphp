<?php

namespace ManaPHP\Cli;

interface ConsoleInterface
{
    /**
     * @return bool
     */
    public function isSupportColor();

    /**
     * @param string $text
     * @param int    $options
     * @param int    $width
     *
     * @return string
     */
    public function colorize($text, $options = 0, $width = 0);

    /**
     * @return void
     */
    public function sampleColorizer();

    /**
     * @param string|array $message
     * @param int          $options
     *
     * @return static
     */
    public function write($message, $options = 0);

    /**
     * @param string|array $message
     * @param int          $options
     *
     * @return static
     */
    public function writeLn($message = '', $options = 0);

    /**
     * @param string|array $message
     * @param int          $options
     *
     * @return static
     */
    public function debug($message = '', $options = 0);

    /**
     * @param string|array $message
     *
     * @return void
     */
    public function info($message);

    /**
     * @param string|array $message
     *
     * @return void
     */
    public function warn($message);

    /**
     * @param string|array $message
     *
     * @return void
     */
    public function success($message);

    /**
     * @param string|array $message
     * @param int          $code
     *
     * @return int
     */
    public function error($message, $code = 1);

    /**
     * @param string|array     $message
     * @param int|float|string $value
     */
    public function progress($message, $value = null);

    /**
     * @return string
     */
    public function read();

    /**
     * @param string $message
     *
     * @return string
     */
    public function ask($message);
}