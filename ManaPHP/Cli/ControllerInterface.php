<?php
namespace ManaPHP\Cli;

/**
 * Interface ManaPHP\Cli\ControllerInterface
 *
 * @package controller
 */
interface ControllerInterface
{
    /**
     * @return array
     */
    public function getCommands();
}