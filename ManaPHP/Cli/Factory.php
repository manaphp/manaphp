<?php
namespace ManaPHP\Cli;

use ManaPHP\Di\FactoryDefault;

class Factory extends FactoryDefault
{
    public function __construct()
    {
        parent::__construct();

        $this->_components = array_merge($this->_components, [
            'cliHandler' => 'ManaPHP\Cli\Handler',
            'console' => 'ManaPHP\Cli\Console',
            'arguments' => 'ManaPHP\Cli\Arguments',
            'commandInvoker' => 'ManaPHP\Cli\CommandInvoker',
            'errorHandler' => 'ManaPHP\Cli\ErrorHandler']);
    }
}