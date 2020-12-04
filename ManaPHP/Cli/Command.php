<?php

namespace ManaPHP\Cli;

use ManaPHP\Http\Dispatcher\NotFoundActionException;
use ReflectionMethod;

/**
 * @property-read \ManaPHP\Caching\CacheInterface   $viewsCache
 * @property-read \ManaPHP\Messaging\QueueInterface $msgQueue
 * @property-read \ManaPHP\Cli\ConsoleInterface     $console
 * @property-read \ManaPHP\Cli\RequestInterface     $request
 * @property-read \ManaPHP\Cli\HandlerInterface     $cliHandler
 */
abstract class Command extends \ManaPHP\Controller
{
    /**
     * @param string $action
     *
     * @return void
     * @throws NotFoundActionException
     *
     */
    public function validateInvokable($action)
    {
        $method = $action . 'Action';

        if (!in_array($method, get_class_methods($this), true)) {
            throw new NotFoundActionException(['`%s::%s` method does not exist', static::class, $method]);
        }
    }

    /**
     * show this help information
     *
     * @return void
     */
    public function helpAction()
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
            if (!preg_match('#^([a-z].*)Action$#', $method, $match)) {
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

            $method_name = str_pad(basename($method, 'Action'), 10);
            $action = $this->console->colorize($method_name, Console::FC_YELLOW) . ' ' . $description;
            $this->console->writeLn($action);

            $defaultValues = [];
            foreach ($rm->getParameters() as $parameter) {
                if ($parameter->isDefaultValueAvailable()) {
                    $defaultValues[$parameter->getName()] = $parameter->getDefaultValue();
                }
            }

            $params = [];
            foreach ($lines as $line) {
                if (!str_contains($line, '@param')) {
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

                    $ov = $this->console->colorize($option, Console::FC_CYAN, $width);
                    $vv = $value ? "  $value" : '';
                    $dv = isset($defaultValues[$name]) ? " (default: $defaultValues[$name])" : '';
                    $this->console->writeLn(['    %s %s %s', $ov, $vv, $dv]);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getActions()
    {
        $actions = [];
        foreach (get_class_methods($this) as $method) {
            if ($method === 'helpAction' || $method[0] === '_') {
                continue;
            }
            if (preg_match('#^([a-z].*)Action$#', $method, $match)) {
                $actions[] = $match[1];
            }
        }

        return $actions;
    }
}