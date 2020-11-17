<?php

namespace ManaPHP\Cli;

use ManaPHP\Di\FactoryDefault;

class Factory extends FactoryDefault
{
    public function __construct()
    {
        parent::__construct();

        $this->_definitions = array_merge(
            $this->_definitions, [
                'cliHandler'   => 'ManaPHP\Cli\Handler',
                'console'      => 'ManaPHP\Cli\Console',
                'request'      => 'ManaPHP\Cli\Request',
                'errorHandler' => 'ManaPHP\Cli\ErrorHandler',

                'bashCompletionCommand' => 'ManaPHP\Cli\Commands\BashCompletionCommand',
                'dateCommand'           => 'ManaPHP\Cli\Commands\DateCommand',
                'excelCommand'          => 'ManaPHP\Cli\Commands\ExcelCommand',
                'fiddlerCommand'        => 'ManaPHP\Cli\Commands\FiddlerCommand',
                'frameworkCommand'      => 'ManaPHP\Cli\Commands\FrameworkCommand',
                'helpCommand'           => 'ManaPHP\Cli\Commands\HelpCommand',
                'keyCommand'            => 'ManaPHP\Cli\Commands\KeyCommand',
                'passwordCommand'       => 'ManaPHP\Cli\Commands\PasswordCommand',
                'pharCommand'           => 'ManaPHP\Cli\Commands\PharCommand',
                'serveCommand'          => 'ManaPHP\Cli\Commands\ServeCommand',
                'swordCommand'          => 'ManaPHP\Html\Renderer\Engine\Sword\Command',
                'mongodbCommand'        => 'ManaPHP\Data\Mongodb\Command',
                'dotenvCommand'         => 'ManaPHP\Configuration\Dotenv\Command',
                'bosCommand'            => 'ManaPHP\Bos\Command',
                'dbCommand'             => 'ManaPHP\Data\Db\Command',
                'tracerCommand'         => 'ManaPHP\Event\Tracer\Command',
            ]
        );
    }
}