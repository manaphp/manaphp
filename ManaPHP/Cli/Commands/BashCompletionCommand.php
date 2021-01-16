<?php

namespace ManaPHP\Cli\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;
use ReflectionClass;
use ReflectionMethod;

class BashCompletionCommand extends Command
{
    /**
     * @return string[]
     */
    protected function _getCommands()
    {
        $commands = [];

        foreach ($this->_di->getDefinitions('*Command') as $name => $_) {
            $commands[] = Str::underscore(basename($name, 'Command'));
        }

        return $commands;
    }

    /**
     * @param string $command
     *
     * @return string[]
     */
    protected function _getActions($command)
    {
        $actions = [];
        try {
            if (!$commandClassName = $this->_di->getDefinition(Str::variablize($command) . 'Command')) {
                return [];
            }

            $rc = new ReflectionClass($commandClassName);
            foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (!$method->isStatic() && preg_match('#^(.*)Action$#', $method->getShortName(), $matches) === 1) {
                    $actions[] = Str::underscore($matches[1]);
                }
            }
        } catch (\Exception $e) {
        }

        return $actions;
    }

    /**
     * @param string $command
     * @param string $action
     *
     * @return string[]
     */
    protected function _getArgumentNames($command, $action)
    {
        if (!$commandClassName = $this->_di->getDefinition(Str::variablize($command) . 'Command')) {
            return [];
        }

        $action = Str::camelize($action) . 'Action';
        if (!method_exists($commandClassName, $action)) {
            return [];
        }

        $arguments = [];
        foreach ((new ReflectionMethod($commandClassName, $action))->getParameters() as $parameter) {
            $arguments[] = '--' . strtr($parameter->name, '_', '-');
        }

        return $arguments;
    }

    /**
     * @param string $command
     * @param string $action
     * @param string $argumentName
     *
     * @return array
     */
    protected function _getArgumentValues($command, $action, $argumentName)
    {
        if (!$commandClassName = $this->_di->getDefinition(Str::variablize($command) . 'Command')) {
            return [];
        }

        $argument_values = [];
        $action = Str::variablize($action) . 'Completion';
        if (method_exists($commandClassName, $action)) {
            try {
                $argument_values = $this->getInstance($commandClassName)->$action($argumentName);
            } catch (\Exception $e) {
            }
        }

        return $argument_values;
    }

    /**
     * @param array  $words
     * @param string $current
     *
     * @return array
     */
    protected function _filterWords($words, $current)
    {
        $filtered_words = [];

        foreach ($words as $word) {
            if ($current === '' || stripos($word, $current) !== false) {
                $filtered_words[] = $word;
            }
        }

        return $filtered_words;
    }

    /**
     * complete for bash
     *
     * @return int
     */
    public function completeAction()
    {
        $arguments = array_slice($GLOBALS['argv'], 3);
        $position = (int)$arguments[0];

        $arguments = array_slice($arguments, 1);

        $count = count($arguments);

        $command = null;
        if ($count > 1) {
            $command = $arguments[1];
        }

        $action = null;
        if ($count > 2) {
            $action = $arguments[2];
            if ($action !== '' && $action[0] === '-') {
                $action = 'default';
            }
        }

        $previous = $position > 0 ? $arguments[$position - 1] : null;

        $current = $arguments[$position] ?? '';

        if ($position === 1) {
            $words = $this->_getCommands();
        } elseif ($current !== '' && $current[0] === '-') {
            $words = $this->_getArgumentNames($command, $action);
            foreach ($words as $k => $word) {
                if (in_array($word, $arguments, true)) {
                    unset($words[$k]);
                }
            }

            $words = array_values($words);
        } elseif ($position === 2) {
            $words = $this->_getActions($command);
        } else {
            $words = $this->_getArgumentValues($command, $action, $previous);
        }

        $this->console->writeLn(implode(' ', $this->_filterWords($words, $current)));

        return 0;
    }

    /**
     * install bash completion script
     *
     * @return int
     */
    public function installAction()
    {
        $content = <<<'EOT'
#!/bin/bash

_manacli(){
   COMPREPLY=( $(./manacli.php bash_completion complete $COMP_CWORD "${COMP_WORDS[@]}") )
   return 0;
}

complete -F _manacli manacli
EOT;
        $file = '/etc/bash_completion.d/manacli';

        if (DIRECTORY_SEPARATOR === '\\') {
            return $this->console->error('Windows system is not support bash completion!');
        }

        try {
            LocalFS::filePut($file, PHP_EOL === '\n' ? $content : str_replace("\r", '', $content));
            LocalFS::chmod($file, 0755);
        } catch (\Exception $e) {
            return $this->console->error('write bash completion script failed: ' . $e->getMessage());
        }

        $this->console->writeLn('install bash completion script successfully');
        $this->console->writeLn("please execute `source $file` command to become effective");
        return 0;
    }
}