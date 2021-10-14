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

            'bashCompletionCommand' => 'ManaPHP\Commands\BashCompletionCommand',
            'dateCommand'           => 'ManaPHP\Commands\DateCommand',
            'excelCommand'          => 'ManaPHP\Commands\ExcelCommand',
            'fiddlerCommand'        => 'ManaPHP\Commands\FiddlerCommand',
            'frameworkCommand'      => 'ManaPHP\Commands\FrameworkCommand',
            'helpCommand'           => 'ManaPHP\Commands\HelpCommand',
            'keyCommand'            => 'ManaPHP\Commands\KeyCommand',
            'passwordCommand'       => 'ManaPHP\Commands\PasswordCommand',
            'pharCommand'           => 'ManaPHP\Commands\PharCommand',
            'serveCommand'          => 'ManaPHP\Commands\ServeCommand',
            'swordCommand'          => 'ManaPHP\Commands\SwordCommand',
            'mongodbCommand'        => 'ManaPHP\Commands\MongodbCommand',
            'dotenvCommand'         => 'ManaPHP\Commands\DotenvCommand',
            'bosCommand'            => 'ManaPHP\Commands\BosCommand',
            'dbCommand'             => 'ManaPHP\Commands\DbCommand',
            'listCommand'           => 'ManaPHP\Commands\ListCommand',
            'debuggerCommand'       => 'ManaPHP\Commands\DebuggerCommand',
            'configCommand'         => 'ManaPHP\Commands\ConfigCommand',
            'versionCommand'        => 'ManaPHP\Commands\VersionCommand',
            'aopCommand'            => 'ManaPHP\Commands\AopCommand',
            'cryptCommand'          => 'ManaPHP\Commands\CryptCommand',
            'uuidCommand'           => 'ManaPHP\Commands\UuidCommand',

            'httpClientTracer' => 'ManaPHP\Tracers\HttpClientTracer',
        ];
}