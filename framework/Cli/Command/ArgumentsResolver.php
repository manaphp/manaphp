<?php
declare(strict_types=1);

namespace ManaPHP\Cli\Command;

use ManaPHP\Cli\Command;
use ManaPHP\Component;
use ManaPHP\Validating\Validator\ValidateFailedException;
use ReflectionMethod;

/**
 * @property-read \Psr\Container\ContainerInterface      $container
 * @property-read \ManaPHP\Validating\ValidatorInterface $validator
 * @property-read \ManaPHP\Cli\RequestInterface          $request
 */
class ArgumentsResolver extends Component implements ArgumentsResolverInterface
{
    public function resolve(Command $command, string $method): array
    {
        $args = [];
        $missing = [];

        $container = $this->container;

        $rMethod = new ReflectionMethod($command, $method);
        $rParameters = $rMethod->getParameters();
        foreach ($rParameters as $rParameter) {
            $name = $rParameter->getName();
            $value = null;

            $type = $rParameter->getType();
            if ($type !== null) {
                $type = $type->getName();
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
            } elseif ($type === 'NULL') {
                /** @noinspection PhpConditionAlreadyCheckedInspection */
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
}