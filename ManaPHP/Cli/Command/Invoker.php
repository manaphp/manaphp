<?php

namespace ManaPHP\Cli\Command;

use ManaPHP\Component;

/**
 * Class Invoker
 * @package ManaPHP\Cli\Command
 *
 * @property-read \ManaPHP\Cli\ArgumentsInterface $arguments
 */
class Invoker extends Component implements InvokerInterface
{
    /**
     * @param \ReflectionParameter[] $parameters
     *
     * @return array
     */
    protected function _getShortNames($parameters)
    {
        $shortNames = [];
        foreach ($parameters as $parameter) {
            $rc = $parameter->getClass();
            if ($rc && is_subclass_of($rc->getName(), Component::class)) {
                continue;
            }

            $name = $parameter->getName();
            $short = $name[0];
            if (isset($names[$short])) {
                $shortNames[$short] = false;
            } else {
                $shortNames[$short] = $name;
            }
        }
        return array_flip(array_filter($shortNames));
    }

    /**
     * @param \ManaPHP\Cli\ControllerInterface $controller
     * @param string                           $command
     *
     * @return array
     */
    protected function _buildArgs($controller, $command)
    {
        $args = [];

        $di = $this->_di;

        $parameters = (new \ReflectionMethod($controller, $command . 'Command'))->getParameters();
        $shortNames = $this->_getShortNames($parameters);

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $value = null;

            $type = $parameter->getClass();
            if ($type !== null) {
                $type = $type->getName();
            } else {
                if ($parameter->isDefaultValueAvailable()) {
                    $type = gettype($parameter->getDefaultValue());
                }
            }

            if ($type !== null && is_subclass_of($type, Component::class)) {
                if ($di->has($name)) {
                    $value = $di->get($name);
                } elseif ($di->has($type)) {
                    $value = $di->get($type);
                } else {
                    $value = $di->getShared($type);
                }
            } elseif ($this->arguments->hasOption($name)) {
                $value = $this->arguments->getOption($name);
            } elseif (isset($shortNames[$name]) && $this->arguments->hasOption($shortNames[$name])) {
                $value = $this->arguments->getOption($shortNames[$name]);
            } elseif (count($this->arguments->getValues()) === 1) {
                $value = $this->arguments->getValues()[0];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            } else {
                $this->arguments->getOption($name . (isset($shortNames[$name]) ? ":$shortNames[$name]" : ''));
            }

            switch ($type) {
                case 'boolean':
                    $value = (bool)$value;
                    break;
                case 'integer':
                    $value = (int)$value;
                    break;
                case 'double':
                    $value = (float)$value;
                    break;
                case 'string':
                    $value = (string)$value;
                    break;
                case 'array':
                    $value = is_string($value) ? explode(',', $value) : (array)$value;
                    break;
            }

            if ($parameter->isArray()) {
                $args[] = (array)$value;
            } else {
                $args[] = $value;
            }
        }

        return $args;
    }

    /**
     * @param \ManaPHP\Cli\ControllerInterface $controller
     * @param string                           $command
     *
     * @return mixed
     * @throws \ManaPHP\Cli\Command\NotFoundException
     */
    public function invoke($controller, $command)
    {
        $commandMethod = $command . 'Command';

        if (!method_exists($controller, $commandMethod)) {
            throw new NotFoundException([
                '`:controller:::action` is not found',
                'action' => $commandMethod,
                'controller' => get_class($controller)
            ]);
        }

        $args = $this->_buildArgs($controller, $command);

        switch (count($args)) {
            case 0:
                $r = $controller->$commandMethod();
                break;
            case 1:
                $r = $controller->$commandMethod($args[0]);
                break;
            default:
                $r = call_user_func_array([$controller, $commandMethod], $args);
                break;
        }

        return $r;
    }
}