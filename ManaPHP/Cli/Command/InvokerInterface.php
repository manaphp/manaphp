<?php
namespace ManaPHP\Cli\Command;

interface InvokerInterface
{
    /**
     * @param \ManaPHP\Cli\ControllerInterface $controller
     * @param string                           $command
     *
     * @return mixed
     */
    public function invoke($controller, $command);
}