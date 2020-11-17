<?php

namespace ManaPHP\Cli\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Cli\Console;
use ManaPHP\Helper\Str;
use ReflectionClass;

/**
 * Class ManaPHP\Cli\Commands\HelpCommand
 *
 * @package ManaPHP\Cli\Commands
 */
class HelpCommand extends Command
{
    /**
     * list all actions
     *
     * @return int
     */
    public function listAction()
    {
        $builtin_commands = [];
        $app_commands = [];
        foreach ($this->_di->getDefinitions() as $name => $definition) {
            if (is_string($definition) && str_ends_with($name, 'Command')) {
                if (str_starts_with($definition, 'App\\')) {
                    $app_commands[$name] = $definition;
                } else {
                    $builtin_commands[$name] = $definition;
                }
            }
        }

        $this->console->writeLn('manaphp commands:', Console::FC_GREEN | Console::AT_BOLD);
        ksort($builtin_commands);
        foreach ($builtin_commands as $name => $definition) {
            $plainName = ucfirst($name);
            $command = Str::underscore(basename($plainName, 'Command'));
            $this->console->writeLn(' - ' . $this->console->colorize($command, Console::FC_YELLOW));
            $actions = $this->_getActions($definition);

            $width = max(max(array_map('strlen', array_keys($actions))), 18);
            foreach ($actions as $action => $description) {
                $colored_action = $this->console->colorize($action, Console::FC_CYAN, $width);
                $this->console->writeLn('    ' . $colored_action . ' ' . $description);
            }
        }

        ksort($app_commands);
        $this->console->writeLn('application commands:', Console::FC_GREEN | Console::AT_BOLD);
        foreach ($app_commands as $name => $definition) {
            $plainName = ucfirst($name);
            $command = Str::underscore(basename($plainName, 'Command'));
            $this->console->writeLn(' - ' . $this->console->colorize($command, Console::FC_YELLOW));
            $actions = $this->_getActions($definition);

            $width = max(max(array_map('strlen', array_keys($actions))), 18);
            foreach ($actions as $action => $description) {
                $colored_action = $this->console->colorize($action, Console::FC_CYAN, $width);
                $this->console->writeLn('    ' . $colored_action . ' ' . $description);
            }
        }
        return 0;
    }

    /**
     * @param string $commandClassName
     *
     * @return array
     */
    protected function _getActions($commandClassName)
    {
        $actions = [];
        $rc = new ReflectionClass($commandClassName);
        foreach (get_class_methods($commandClassName) as $method) {
            if (preg_match('#^(.*)Action$#', $method, $match) !== 1) {
                continue;
            }
            if ($match[1] === 'help') {
                continue;
            }

            $action = $match[1];

            $description = '';
            foreach (preg_split('#[\r\n]+#', $rc->getMethod($match[0])->getDocComment()) as $line) {
                $line = trim($line, "\t /*\r\n");
                if (!$line) {
                    continue;
                }

                if ($line[0] !== '@') {
                    $description = $line;
                }
                break;
            }
            $actions[$action] = $description;
        }

        ksort($actions);

        return $actions;
    }
}