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