<?php

namespace ManaPHP\Cli;

class Provider extends \ManaPHP\Di\Provider
{
    protected $definitions
        = [
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
            'listCommand'           => 'ManaPHP\Cli\Commands\ListCommand',
            'debuggerCommand'       => 'ManaPHP\Debugging\DebuggerPlugin\Command',
            'configCommand'         => 'ManaPHP\Configuration\Command',
            'versionCommand'        => 'ManaPHP\Cli\Commands\VersionCommand',
            'aopCommand'            => 'ManaPHP\Aop\Command',
            'cryptCommand'          => 'ManaPHP\Security\Command',
            'uuidCommand'           => 'ManaPHP\Cli\Commands\UuidCommand',

            'httpClientTracer' => 'ManaPHP\Http\Client\Tracer',
        ];
}