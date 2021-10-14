<?php

namespace ManaPHP\Cli;

class Provider extends \ManaPHP\Di\Provider
{
    protected $definitions
        = [
            'cliHandler'            => 'ManaPHP\Cli\Handler',
            'console'               => 'ManaPHP\Cli\Console',
            'request'               => 'ManaPHP\Cli\Request',
            'errorHandler'          => 'ManaPHP\Cli\ErrorHandler',
            'commandManager'        => "ManaPHP\Cli\Command\Manager",
        ];
}