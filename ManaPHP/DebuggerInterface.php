<?php
namespace ManaPHP;

/**
 * Interface ManaPHP\DebuggerInterface
 *
 * @package debugger
 */
interface DebuggerInterface
{
    /**
     * @return void
     */
    public function start();

    /**
     * @param mixed  $value
     * @param string $name
     *
     * @return static
     */
    public function var_dump($value, $name = null);

    /**
     * @return string
     */
    public function output();

    /**
     * @return void
     */
    public function save();

    /**
     * @return string
     */
    public function getUrl();
}