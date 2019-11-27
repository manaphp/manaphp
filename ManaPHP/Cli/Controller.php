<?php

namespace ManaPHP\Cli;

use ReflectionMethod;

/**
 * Class ManaPHP\Cli\Controller
 *
 * @package controller
 *
 * @property-read \ManaPHP\Di|\ManaPHP\DiInterface $di
 * @property-read \ManaPHP\CacheInterface          $viewsCache
 * @property-read \ManaPHP\Message\QueueInterface  $messageQueue
 * @property-read \ManaPHP\Cli\ConsoleInterface    $console
 * @property-read \ManaPHP\Cli\RequestInterface    $request
 * @property-read \ManaPHP\Cli\HandlerInterface    $cliHandler
 */
abstract class Controller extends \ManaPHP\Controller
{
    /**
     * show this help information
     */
    public function helpCommand()
    {
        $args = $this->cliHandler->getArgs();
        if (isset($args[2]) && $args[2] !== 'help' && $args[2][0] !== '-') {
            $actionName = $args[2];
        } elseif (isset($args[3]) && $args[2] === 'help' && $args[3][0] !== '-') {
            $actionName = $args[3];
        } else {
            $actionName = '';
        }

        foreach (get_class_methods($this) as $method) {
            if (!preg_match('#^([a-z].*)Command$#', $method, $match)) {
                continue;
            }

            if ($actionName && $match[1] !== $actionName) {
                continue;
            }

            $rm = new ReflectionMethod($this, $method);
            if (!$rm->isPublic()) {
                continue;
            }

            $lines = [];
            foreach (preg_split('#[\r\n]+#', $rm->getDocComment()) as $line) {
                $lines[] = trim($line, "\t /*\r\n");
            }

            $description = '';
            foreach ($lines as $line) {
                if (!$line) {
                    continue;
                }

                if ($line[0] !== '@') {
                    $description = $line;
                }
                break;
            }

            $command = $this->console->colorize(str_pad(basename($method, 'Command'), 10), Console::FC_YELLOW) . ' ' . $description;
            $this->console->writeLn($command);

            $defaultValues = [];
            foreach ($rm->getParameters() as $parameter) {
                if ($parameter->isDefaultValueAvailable()) {
                    $defaultValues[$parameter->getName()] = $parameter->getDefaultValue();
                }
            }

            $params = [];
            foreach ($lines as $line) {
                if (strpos($line, '@param') === false) {
                    continue;
                }

                $parts = preg_split('#\s+#', $line, 4);
                if (count($parts) < 3 || $parts[0] !== '@param') {
                    continue;
                }
                $name = substr($parts[2], 1);
                $type = $parts[1];

                if (isset($defaultValues[$name])) {
                    if ($type === 'bool' || $type === 'boolean') {
                        $defaultValues[$name] = $defaultValues[$name] ? 'YES' : 'NO';
                    } elseif ($type === 'int' || $type === 'integer') {
                        $defaultValues[$name] = (int)$defaultValues[$name];
                    } elseif ($type === 'float' || $type === 'double') {
                        $defaultValues[$name] = (float)$defaultValues[$name];
                    } elseif ($type === 'string') {
                        $defaultValues[$name] = json_stringify($defaultValues[$name]);
                    } elseif ($type === 'array') {
                        $defaultValues[$name] = json_stringify($defaultValues[$name]);
                    }
                }

                $params[$name] = isset($parts[3]) ? trim($parts[3]) : '';
            }

            if ($params) {
                $shortNames = [];
                foreach ($params as $name => $description) {
                    $short = $name[0];
                    if (isset($shortNames[$short])) {
                        $shortNames[$short] = false;
                    } else {
                        $shortNames[$short] = $name;
                    }
                }
                $shortNames = array_flip(array_filter($shortNames));

                $width = 1;
                foreach ($params as $name => $description) {
                    $width = max($width, strlen($name) + 2 + (isset($shortNames[$name]) ? 4 : 0));
                }
                $this->console->writeLn('  Options:');

                foreach ($params as $name => $value) {
                    $option = '--' . $name;
                    if (isset($shortNames[$name])) {
                        $option .= ', -' . $shortNames[$name];
                    }
                    $this->console->writeLn(['    :option :value :default',
                        'option' => $this->console->colorize($option, Console::FC_CYAN, $width),
                        'value' => $value ? "  $value" : '',
                        'default' => isset($defaultValues[$name]) ? " (default: $defaultValues[$name])" : '']);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getCommands()
    {
        $commands = [];
        foreach (get_class_methods($this) as $method) {
            if ($method === 'helpCommand' || $method[0] === '_') {
                continue;
            }
            if (preg_match('#^([a-z].*)Command$#', $method, $match)) {
                $commands[] = $match[1];
            }
        }

        return $commands;
    }
}