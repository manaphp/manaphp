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
     * @param string $str
     * @param array  $context
     *
     * @return static
     */
    public function write($str, $context = []);

    /**
     * @param string $str
     * @param array  $context
     *
     * @return static
     */
    public function writeLn($str, $context = []);

    /**
     * @param string $str
     * @param array  $context
     * @param int    $code
     *
     * @return int
     */
    public function error($str, $context = [], $code = 1);
}