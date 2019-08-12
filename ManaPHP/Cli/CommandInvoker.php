<?php

namespace ManaPHP\Cli;

use ManaPHP\Cli\CommandInvoker\NotFoundException;
use ManaPHP\Component;
use ManaPHP\Validator\ValidateFailedException;
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
     * @param \ManaPHP\Cli\Controller $controller
     * @param string                  $command
     *
     * @return array
     */
    protected function _buildArgs($controller, $command)
    {
        $args = [];
        $missing = [];

        $di = $this->_di;

        $parameters = (new ReflectionMethod($controller, $command . 'Command'))->getParameters();

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $value = null;

            if ($className = ($c = $parameter->getClass()) ? $c->getName() : null) {
                $value = $di->has($name) ? $di->getShared($name) : $di->getShared($className);
            } elseif (strpos($name, 'Service') !== false) {
                $value = $di->getShared($name);
            } elseif ($this->request->has($name)) {
                $value = $this->request->get($name);
            } elseif ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            } elseif (count($parameters) === 1 && count($this->request->getValues()) === 1) {
                $value = $this->request->getValues()[0];
            }

            if ($value === null) {
                $missing[] = $name;
                continue;
            }

            $type = $parameter->getType();
            if ($type !== null) {
                $type = $type->getName();
            } elseif ($parameter->isDefaultValueAvailable()) {
                $type = gettype($parameter->getDefaultValue());
            }

            switch ($type) {
                case 'boolean':
                case 'bool':
                    $value = $this->validator->validateValue($name, $value, ['bool']);
                    break;
                case 'integer':
                case 'int':
                    $value = $this->validator->validateValue($name, $value, ['int']);
                    break;
                case 'double':
                case 'float':
                    $value = $this->validator->validateValue($name, $value, ['float']);
                    break;
                case 'string':
                    $value = (string)$value;
                    break;
                case 'array':
                    $value = is_string($value) ? explode(',', $value) : (array)$value;
                    break;
            }

            $args[] = $value;
        }

        if ($missing) {
            $errors = [];
            foreach ($missing as $field) {
                $errors[$field] = $this->validator->createError('required', $field);
            }
            throw new ValidateFailedException($errors);
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