<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Utility\Text;

/**
 * Class CronController
 * @package ManaPHP\Cli\Controllers
 * @property \ManaPHP\Cli\Command\Invoker $commandInvoker
 */
class CronController extends Controller
{
    public function runCommand()
    {
        $arguments = $GLOBALS['argv'];

        if (count($arguments) < 4) {
            $this->logger->error(['cron command line too short: `:command`.', 'command' => implode(' ', array_slice($arguments, 1))], 'cron');
            return $this->console->error('missing cron name');
        }

        array_shift($arguments);//manacli
        array_shift($arguments);//cron
        array_shift($arguments);//run
        $simArguments = array_merge([$GLOBALS['argv'][0]], $arguments);
        while (1) {
            $a = array_shift($arguments);
            if ($a[0] === '-') {
                array_shift($arguments);
            } else {
                break;
            }
        }

        $controller = Text::camelize($a) . 'Controller';

        if (class_exists(__NAMESPACE__ . '\\' . $controller)) {
            $controllerName = __NAMESPACE__ . '\\' . $controller;
        } elseif ($this->alias->has('@ns.cli')) {
            $controllerName = $this->alias->resolveNS('@ns.cli\\' . $controller);
        }
        /**
         * @var \ManaPHP\Cli\Controller $controllerInstance
         */
        $controllerInstance = $this->_di->getShared($controllerName);
        $commands = $controllerInstance->getCommands();
        if (count($commands) === 1) {
            $commandName = $commands[0];
            if ($arguments && $arguments[0] === $commandName) {
                array_shift($arguments);
            }
        } else {
            $commandName = array_shift($arguments);
        }

        $start_time = microtime(true);
        $commandLine = implode(' ', array_slice($simArguments, 1));
        $this->logger->info(str_repeat('*====', 10), 'cron');
        $this->logger->info(['begin: `:command`.', 'command' => $commandLine], 'cron');
        try {
            $this->arguments->parse($simArguments);
            $this->commandInvoker->invoke($controllerInstance, $commandName);
            $use_time = round(microtime(true) - $start_time, 3);
            $this->logger->info(['end: `:command`, :time seconds elapsed.', 'command' => $commandLine, 'time' => $use_time], 'cron');
        } catch (\Exception $e) {
            $this->logger->info(['failed because of exception: `:command`', 'command' => $commandLine]);
            $this->logger->error($e, 'cron');
        }

        return 0;
    }

}