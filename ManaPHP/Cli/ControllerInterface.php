<?php
namespace ManaPHP\Cli;

interface ControllerInterface
{
    /**
     * @return array
     */
    public function getCommands();
}