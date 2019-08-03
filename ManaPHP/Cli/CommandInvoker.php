<?php

namespace ManaPHP\Cli;

use ManaPHP\Cli\CommandInvoker\NotFoundException;
use ManaPHP\Component;
use ReflectionMethod;

/**
 * Class Invoker
 * @package ManaPHP\Cli\Command
 *
 * @property-read \ManaPHP\Cli\RequestInterface $request
 */
class CommandInvoker extends Component implements CommandInvokerInterface
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
     * @param \ManaPHP\Cli\Controller $controller
     * @param string                  $command
     *
     * @return array
     */
    protected function _buildArgs($controller, $command)
    {
        $args = [];

        $di = $this->_di;

        $parameters = (new ReflectionMethod($controller, $command . 'Command'))->getParameters();
        $shortNames = $this->_getShortNames($parameters);

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $value = null;

            $type = $parameter->getClass();
            if ($type !== null) {
                $type = $type->getName();
            } elseif ($parameter->isDefaultValueAvailable()) {
                $type = gettype($parameter->getDefaultValue());
            }

            if ($className = ($c = $parameter->getClass()) ? $c->getName() : null) {
                $value = $di->has($name) ? $di->getShared($name) : $di->getShared($className);
            } elseif ($this->request->has($name)) {
                $value = $this->request->get($name);
            } elseif (isset($shortNames[$name]) && $this->request->has($shortNames[$name])) {
                $value = $this->request->get($shortNames[$name]);
            } elseif (count($this->request->getValues()) === 1) {
                $value = $this->request->getValues()[0];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            } elseif ($di->has($name)) {
                $value = $di->getShared($name);
            } else {
                $this->request->get($name . (isset($shortNames[$name]) ? ":$shortNames[$name]" : ''));
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
     * @param \ManaPHP\Cli\Controller $controller
     * @param string                  $command
     *
     * @return mixed
     * @throws \ManaPHP\Cli\CommandInvoker\NotFoundException
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
        return $controller->$commandMethod(...$args);
    }
}