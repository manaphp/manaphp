<?php
namespace ManaPHP\Cli;

/**
 * Interface ManaPHP\Cli\ControllerInterface
 *
 * @package ManaPHP\Cli
 */
interface ControllerInterface
{
    /**
     * @return array
     */
    public function getCommands();
}