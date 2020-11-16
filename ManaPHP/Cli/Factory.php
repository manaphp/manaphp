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

                'aclCommand'            => 'ManaPHP\Cli\Commands\AclCommand',
                'bashCompletionCommand' => 'ManaPHP\Cli\Commands\BashCompletionCommand',
                'bosCommand'            => 'ManaPHP\Cli\Commands\BosCommand',
                'dateCommand'           => 'ManaPHP\Cli\Commands\DateCommand',
                'dbCommand'             => 'ManaPHP\Cli\Commands\DbCommand',
                'dotenvCommand'         => 'ManaPHP\Cli\Commands\DotenvCommand',
                'excelCommand'          => 'ManaPHP\Cli\Commands\ExcelCommand',
                'fiddlerCommand'        => 'ManaPHP\Cli\Commands\FiddlerCommand',
                'frameworkCommand'      => 'ManaPHP\Cli\Commands\FrameworkCommand',
                'helpCommand'           => 'ManaPHP\Cli\Commands\HelpCommand',
                'keyCommand'            => 'ManaPHP\Cli\Commands\KeyCommand',
                'mongodbCommand'        => 'ManaPHP\Cli\Commands\MongodbCommand',
                'passwordCommand'       => 'ManaPHP\Cli\Commands\PasswordCommand',
                'pharCommand'           => 'ManaPHP\Cli\Commands\PharCommand',
                'rpcCommand'            => 'ManaPHP\Cli\Commands\RpcCommand',
                'serveCommand'          => 'ManaPHP\Cli\Commands\ServeCommand',
                'swordCommand'          => 'ManaPHP\Cli\Commands\SwordCommand',
                'viewCommand'           => 'ManaPHP\Cli\Commands\ViewCommand'
            ]
        );
    }
}