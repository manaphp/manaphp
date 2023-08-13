<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\FactoryInterface;
use ManaPHP\Di\InspectorInterface;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;
use ReflectionClass;
use ReflectionMethod;

class BashCompletionCommand extends Command
{
    #[Inject] protected InspectorInterface $inspector;
    #[Inject] protected Command\ManagerInterface $commandManager;
    #[Inject] protected FactoryInterface $factory;

    /**
     * @param string $command
     *
     * @return string[]
     * @noinspection PhpUnusedLocalVariableInspection
     */
    protected function getActions(string $command): array
    {
        $actions = [];
        try {
            if (($commandClassName = $this->commandManager->getCommands()[$command] ?? null) === null) {
                return [];
            }

            $rClass = new ReflectionClass($commandClassName);
            foreach ($rClass->getMethods(ReflectionMethod::IS_PUBLIC) as $rMethod) {
                if (!$rMethod->isStatic() && preg_match('#^(.*)Action$#', $rMethod->getShortName(), $matches) === 1) {
                    $actions[] = Str::snakelize($matches[1]);
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
    protected function getArgumentNames(string $command, string $action): array
    {
        if (!$commandClassName = $this->inspector->getDefinition(Str::camelize($command) . 'Command')) {
            return [];
        }

        $action = Str::pascalize($action) . 'Action';
        if (!method_exists($commandClassName, $action)) {
            return [];
        }

        $arguments = [];
        foreach ((new ReflectionMethod($commandClassName, $action))->getParameters() as $rParameter) {
            $arguments[] = '--' . strtr($rParameter->name, '_', '-');
        }

        return $arguments;
    }

    /**
     * @param string $command
     * @param string $action
     * @param string $argumentName
     * @param string $current
     *
     * @return array
     * @noinspection PhpUnusedLocalVariableInspection
     */
    protected function getArgumentValues(string $command, string $action, string $argumentName, string $current): array
    {
        if (!$commandClassName = $this->inspector->getDefinition(Str::camelize($command) . 'Command')) {
            return [];
        }

        $argument_values = [];
        $action = Str::camelize($action) . 'Completion';
        if (method_exists($commandClassName, $action)) {
            try {
                $argument_values = $this->factory->make($commandClassName)->$action($argumentName, $current);
            } catch (\Exception $e) {
            }
        }

        if ($current !== '' && $argument_values === []) {
            if (str_contains($current, '/')) {
                $dir = substr($current, 0, strrpos($current, '/'));
                foreach (glob($dir . '/*') as $item) {
                    $argument_values[] = is_dir($item) ? $item . '/' : $item;
                }
            } elseif (preg_match('#^\w+$#', $current)) {
                foreach (glob('./*') as $item) {
                    $argument_values[] = is_dir($item) ? basename($item) . '/' : basename($item);
                }
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
    protected function filterWords(array $words, string $current): array
    {
        if ($current === '') {
            return $words;
        }

        if (in_array($current, $words, true)) {
            return [$current];
        }

        $filtered_words = [];

        foreach ($words as $word) {
            if (str_contains($word, $current)) {
                $filtered_words[] = $word;
            }
        }

        if ($filtered_words !== []) {
            return $filtered_words;
        }

        foreach ($words as $word) {
            if (stripos($word, $current) !== false) {
                $filtered_words[] = $word;
            }
        }

        if ($filtered_words !== []) {
            return $filtered_words;
        }

        if (preg_match('#^\w#', $current)) {
            $prefix = $current[0];
        } else {
            $prefix = preg_match('#^\W+\w#', $current, $match) ? $match[0] : '';
        }

        $chars = str_split($current);

        foreach ($words as $word) {
            if (!str_starts_with($word, $prefix)) {
                continue;
            }

            $pos = 0;
            foreach ($chars as $char) {
                if (($pos = stripos($word, $char, $pos)) === false) {
                    break;
                }
            }

            if ($pos !== false) {
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
    public function completeAction(): int
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
            $words = array_keys($this->commandManager->getCommands());
        } elseif ($position === 2) {
            $words = $this->getActions($command);
        } elseif (str_starts_with($previous, '-') && !str_starts_with($current, '-')) {
            $words = $this->getArgumentValues($command, $action, $previous, $current);
        } else {
            $words = $this->getArgumentNames($command, $action);
            foreach ($words as $k => $word) {
                if (in_array($word, $arguments, true)) {
                    unset($words[$k]);
                }
            }

            $words = array_values($words);
        }

        $this->console->writeLn(implode(' ', $this->filterWords($words, $current)));

        return 0;
    }

    /**
     * install bash completion script
     *
     * @return int
     */
    public function installAction(): int
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