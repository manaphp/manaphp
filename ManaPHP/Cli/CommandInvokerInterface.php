<?php
namespace ManaPHP\Cli;

interface CommandInvokerInterface
{
    /**
     * @param \ManaPHP\Cli\Controller $controller
     * @param string                  $command
     *
     * @return mixed
     */
    public function invoke($controller, $command);
}