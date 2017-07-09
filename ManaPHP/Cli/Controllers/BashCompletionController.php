<?php

namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Utility\Text;

class BashCompletionController extends Controller
{
    /**
     * @return array
     */
    protected function _getControllers()
    {
        $controllers = [];

        try {
            foreach ($this->filesystem->glob('@manaphp/Cli/Controllers/*Controller.php') as $file) {
                $controllers[] = Text::underscore(basename($file, 'Controller.php'));
            }

            foreach ($this->filesystem->glob('@cli/*Controller.php') as $file) {
                $controllers[] = Text::underscore(basename($file, 'Controller.php'));
            }
        } catch (\Exception $e) {

        }

        return $controllers;
    }

    /**
     * @param string $controller
     *
     * @return array
     */
    protected function _getCommands($controller)
    {
        $commands = [];
        try {
            $controllerClassName = $this->alias->resolveNS('@ns.cli\\' . Text::camelize($controller)) . 'Controller';
            if (!class_exists($controllerClassName)) {
                $controllerClassName = 'ManaPHP\\Cli\\Controllers\\' . Text::camelize($controller) . 'Controller';
                /** @noinspection NotOptimalIfConditionsInspection */
                if (!class_exists($controllerClassName)) {
                    return [];
                }
            }

            foreach ((new \ReflectionClass($controllerClassName))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if (!$method->isStatic() && $method->isPublic() && preg_match('#^(.*)Command$#', $method->getShortName(), $matches) === 1) {
                    $commands[] = Text::underscore($matches[1]);
                }
            }
        } catch (\Exception $e) {

        }

        return $commands;
    }

    /**
     * @param string $controller
     * @param string $command
     *
     * @return array
     */
    protected function _getArgumentNames($controller, $command)
    {
        $controllerClassName = $this->alias->resolveNS('@ns.cli\\' . Text::camelize($controller)) . 'Controller';
        if (!class_exists($controllerClassName)) {
            $controllerClassName = 'ManaPHP\\Cli\\Controllers\\' . Text::camelize($controller) . 'Controller';
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!class_exists($controllerClassName)) {
                return [];
            }
        }

        $command = Text::camelize($command) . 'Command';
        if (!method_exists($controllerClassName, $command)) {
            return [];
        }

        $arguments = [];
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $docs = (new \ReflectionMethod($controllerClassName, $command))->getDocComment();
        $lines = explode("\n", $docs);
        foreach ($lines as $line) {
            $line = trim($line, "/\r\n\\ \t*");
            $parts = explode(' ', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $tag = $parts[0];
            if ($tag === '@CliParam') {
                $parts = explode(' ', trim($parts[1]), 2);
                foreach (explode(',', $parts[0]) as $item) {
                    $arguments[] = trim($item);
                }
            }
        }

        return $arguments;
    }

    /**
     * @param string $controller
     * @param string $command
     * @param string $argumentName
     *
     * @return array
     */
    protected function _getArgumentValues($controller, $command, $argumentName)
    {
        $controllerClassName = $this->alias->resolveNS('@ns.cli\\' . Text::camelize($controller)) . 'Controller';
        if (!class_exists($controllerClassName)) {
            $controllerClassName = 'ManaPHP\\Cli\\Controllers\\' . Text::camelize($controller) . 'Controller';
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!class_exists($controllerClassName)) {
                return [];
            }
        }

        $argument_values = [];
        $command = Text::camelize($command) . 'Completion';
        if (method_exists($controllerClassName, $command)) {
            try {
                $argument_values = (new $controllerClassName())->$command($argumentName);
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
     * @CliCommand complete for bash
     *
     * @return int
     */
    public function completeCommand()
    {
        $arguments = $this->arguments->get();
        $position = (int)$arguments[0];

        $arguments = array_slice($arguments, 1);

        $count = count($arguments);

        $controller = null;
        if ($count > 1) {
            $controller = $arguments[1];
        }

        $command = null;
        if ($count > 2) {
            $command = $arguments[2];
        }

        $previous = $position > 0 ? $arguments[$position - 1] : null;

        $current = $arguments[$position];

        if ($position === 1) {
            $words = $this->_getControllers();
        } elseif ($position === 2) {
            $words = $this->_getCommands($controller);
        } elseif (($position === 3 && $current === '') || ($current !== '' && $current[0] === '-')) {
            $words = $this->_getArgumentNames($controller, $command);
        } else {
            $words = $this->_getArgumentValues($controller, $command, $previous);
        }

        $this->console->writeLn(implode(' ', $this->_filterWords($words, $current)));

        return 0;
    }

    /**
     * @CliCommand install bash completion script
     */
    public function installCommand()
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
            $this->filesystem->filePut($file, PHP_EOL === '\n' ? $content : str_replace("\r", '', $content));
            $this->filesystem->chmod($file, 0755);
        } catch (\Exception $e) {
            return $this->console->error('write bash completion script failed: ', $e->getMessage());
        }

        $this->console->writeLn('install bash completion script successfully');
        $this->console->writeLn("please execute `source $file` command to become effective");
        return 0;
    }
}