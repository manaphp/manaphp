<?php

namespace ManaPHP\Controller;

use ManaPHP\Component;
use ManaPHP\Validating\Validator\ValidateFailedException;
use ReflectionMethod;

/**
 * @property-read \ManaPHP\Http\RequestInterface         $request
 * @property-read \ManaPHP\Validating\ValidatorInterface $validator
 */
class Invoker extends Component implements InvokerInterface
{
    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['validator'])) {
            $this->_injections['validator'] = $options['validator'];
        }
    }

    /**
     * @param object $instance
     * @param string $method
     *
     * @return array
     */
    public function buildArgs($instance, $method)
    {
        $args = [];
        $missing = [];

        $di = $this->_di;

        $name_of_id = null;
        $parameters = (new ReflectionMethod($instance, $method))->getParameters();
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $value = null;

            $type = $parameter->getType();
            if ($type !== null) {
                $type = (string)$type;
            } elseif ($parameter->isDefaultValueAvailable()) {
                $type = gettype($parameter->getDefaultValue());
            }

            if ($className = ($c = $parameter->getClass()) ? $c->getName() : null) {
                $value = $di->has($name) ? $di->getShared($name) : $di->getShared($className);
            } elseif (str_ends_with($name, 'Service')) {
                $value = $di->getShared($name);
            } elseif ($this->request->has($name)) {
                $value = $this->request->get($name, $type === 'array' ? [] : '');
            } elseif ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            } elseif (count($parameters) === 1) {
                $value = $this->request->getId($name);
            } elseif ($this->request->has('id')) {
                if ($name_of_id === null) {
                    $name_of_id = $name;
                    $value = $this->request->get('id');
                } elseif ($name_of_id !== '') {
                    $missing[] = $name_of_id;
                    $name_of_id = '';
                }
            } elseif ($type === 'NULL') {
                $value = null;
            }

            if ($value === null && $type !== 'NULL') {
                $missing[] = $name;
                continue;
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
     * @param object $instance
     * @param string $method
     *
     * @return mixed
     */
    public function invoke($instance, $method)
    {
        $args = $this->buildArgs($instance, $method);

        return $instance->$method(...$args);
    }
}