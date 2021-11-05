<?php

namespace ManaPHP\Controller;

use ManaPHP\Component;
use ManaPHP\Validating\Validator\ValidateFailedException;
use ManaPHP\Helper\Reflection;

/**
 * @property-read \ManaPHP\Http\RequestInterface         $request
 * @property-read \ManaPHP\Validating\ValidatorInterface $validator
 */
class Invoker extends Component implements InvokerInterface
{
    /**
     * @param \ManaPHP\Controller $controller
     * @param string              $method
     *
     * @return array
     */
    public function buildArgs($controller, $method)
    {
        $args = [];
        $missing = [];

        $container = $this->container;

        $rParameters = Reflection::reflectMethod($controller, $method)->getParameters();
        foreach ($rParameters as $rParameter) {
            $name = $rParameter->getName();
            $value = null;

            $type = $rParameter->getType();
            if ($type !== null) {
                $type = (string)$type->getName();
            } elseif ($rParameter->isDefaultValueAvailable()) {
                $type = gettype($rParameter->getDefaultValue());
            }

            if ($type !== null && str_contains($type, '\\')) {
                $value = $container->has($name) ? $container->get($name) : $container->get($type);
            } elseif (str_ends_with($name, 'Service')) {
                $value = $container->get($name);
            } elseif ($this->request->has($name)) {
                $value = $this->request->get($name, $type === 'array' ? [] : '');
            } elseif ($rParameter->isDefaultValueAvailable()) {
                $value = $rParameter->getDefaultValue();
            } elseif (count($rParameters) === 1 && ($name === 'id' || str_ends_with($name, '_id'))) {
                $value = $this->request->getId($name);
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
     * @param \ManaPHP\Controller $controller
     * @param string              $method
     *
     * @return mixed
     */
    public function invoke($controller, $method)
    {
        $args = $this->buildArgs($controller, $method);

        return $controller->$method(...$args);
    }
}