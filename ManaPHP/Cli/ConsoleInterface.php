<?php
namespace ManaPHP\Cli;

/**
 * Interface ManaPHP\Cli\ConsoleInterface
 *
 * @package ManaPHP\Cli
 */
interface ConsoleInterface
{
    /**
     * @param string $text
     * @param int    $options
     *
     * @return string
     */
    public function colorize($text, $options = 0);

    /**
     * @return void
     */
    public function sampleColorizer();

    /**
     * @param string|array $str
     * @param int          $options
     *
     * @return static
     */
    public function write($str, $options = 0);

    /**
     * @param string|array $str
     * @param int          $options
     *
     * @return static
     */
    public function writeLn($str = '', $options = 0);

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
     * @param  string|array    $message
     * @param int|float|string $value
     */
    public function progress($message, $value = null);
}