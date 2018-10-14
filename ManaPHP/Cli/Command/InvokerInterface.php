<?php
namespace ManaPHP\Cli\Command;

interface InvokerInterface
{
    /**
     * @param \ManaPHP\Cli\Controller $controller
     * @param string                  $command
     *
     * @return mixed
     */
    public function invoke($controller, $command);
}