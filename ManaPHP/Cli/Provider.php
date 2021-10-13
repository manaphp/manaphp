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
            'swordCommand'          => 'ManaPHP\Cli\Commands\SwordCommand',
            'mongodbCommand'        => 'ManaPHP\Cli\Commands\MongodbCommand',
            'dotenvCommand'         => 'ManaPHP\Cli\Commands\DotenvCommand',
            'bosCommand'            => 'ManaPHP\Cli\Commands\BosCommand',
            'dbCommand'             => 'ManaPHP\Cli\Commands\DbCommand',
            'listCommand'           => 'ManaPHP\Cli\Commands\ListCommand',
            'debuggerCommand'       => 'ManaPHP\Cli\Commands\DebuggerCommand',
            'configCommand'         => 'ManaPHP\Cli\Commands\ConfigCommand',
            'versionCommand'        => 'ManaPHP\Cli\Commands\VersionCommand',
            'aopCommand'            => 'ManaPHP\Cli\Commands\AopCommand',
            'cryptCommand'          => 'ManaPHP\Cli\Commands\CryptCommand',
            'uuidCommand'           => 'ManaPHP\Cli\Commands\UuidCommand',

            'httpClientTracer' => 'ManaPHP\Tracers\HttpClientTracer',
        ];
}