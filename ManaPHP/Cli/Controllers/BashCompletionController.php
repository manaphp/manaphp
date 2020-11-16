<?php

namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;
use ReflectionClass;
use ReflectionMethod;

class BashCompletionController extends Controller
{
    /**
     * @return array
     */
    protected function _getControllers()
    {
        $controllers = [];

        try {
            foreach (LocalFS::glob('@manaphp/Cli/Controllers/*Controller.php') as $file) {
                $controllers[] = Str::underscore(basename($file, 'Controller.php'));
            }

            if ($this->alias->has('@cli')) {
                foreach (LocalFS::glob('@cli/*Controller.php') as $file) {
                    $controllers[] = Str::underscore(basename($file, 'Controller.php'));
                }
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
    protected function _getActions($controller)
    {
        $actions = [];
        try {
            $controllerClassName = $this->_getControllerClassName($controller);

            if (!class_exists($controllerClassName)) {
                return [];
            }

            $rc = new ReflectionClass($controllerClassName);
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
     * @param string $controller
     * @param string $action
     *
     * @return array
     */
    protected function _getArgumentNames($controller, $action)
    {
        $controllerClassName = $this->_getControllerClassName($controller);
        if (!class_exists($controllerClassName)) {
            return [];
        }

        $action = Str::camelize($action) . 'Action';
        if (!method_exists($controllerClassName, $action)) {
            return [];
        }

        $arguments = [];
        foreach ((new ReflectionMethod($controllerClassName, $action))->getParameters() as $parameter) {
            $arguments[] = '--' . strtr($parameter->name, '_', '-');
        }

        return $arguments;
    }

    /**
     * @param string $controllerName
     *
     * @return string
     */
    protected function _getControllerClassName($controllerName)
    {
        if ($this->alias->has('@ns.cli')) {
            $controllerClassName = $this->alias->resolveNS('@ns.cli\\' . Str::camelize($controllerName)) . 'Controller';
            if (class_exists($controllerClassName)) {
                return $controllerClassName;
            }
        }

        return 'ManaPHP\\Cli\\Controllers\\' . Str::camelize($controllerName) . 'Controller';
    }

    /**
     * @param string $controller
     * @param string $action
     * @param string $argumentName
     *
     * @return array
     */
    protected function _getArgumentValues($controller, $action, $argumentName)
    {
        $controllerClassName = $this->_getControllerClassName($controller);
        if (!class_exists($controllerClassName)) {
            return [];
        }

        $argument_values = [];
        $action = Str::camelize($action) . 'Completion';
        if (method_exists($controllerClassName, $action)) {
            try {
                $argument_values = $this->getInstance($controllerClassName)->$action($argumentName);
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

        $controller = null;
        if ($count > 1) {
            $controller = $arguments[1];
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
            $words = $this->_getControllers();
        } elseif ($current !== '' && $current[0] === '-') {
            $words = $this->_getArgumentNames($controller, $action);
        } elseif ($position === 2) {
            $words = $this->_getActions($controller);
        } else {
            $words = $this->_getArgumentValues($controller, $action, $previous);
        }

        $this->console->writeLn(implode(' ', $this->_filterWords($words, $current)));

        return 0;
    }

    /**
     * install bash completion script
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