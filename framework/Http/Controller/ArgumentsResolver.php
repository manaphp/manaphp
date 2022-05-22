<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller;

use ManaPHP\Component;
use ManaPHP\Data\ModelInterface;
use ManaPHP\Exception\BadRequestException;
use ManaPHP\Http\Controller;
use ManaPHP\Validating\Validator\ValidateFailedException;
use ReflectionMethod;

/**
 * @property-read \Psr\Container\ContainerInterface      $container
 * @property-read \ManaPHP\Validating\ValidatorInterface $validator
 * @property-read \ManaPHP\Http\RequestInterface         $request
 * @property-read \ManaPHP\Data\Model\ManagerInterface   $modelManager
 */
class ArgumentsResolver extends Component implements ArgumentsResolverInterface
{
    protected function resolveModel(string $model): ModelInterface
    {
        /** @var ModelInterface $instance */

        if (($id = $this->request->get($this->modelManager->getprimaryKey($model), '')) !== '') {
            if (!is_int($id) && !is_string($id)) {
                throw new BadRequestException('id is invalid.');
            }
            /** @var ModelInterface $model */
            $instance = $model::get($id);
        } else {
            $instance = new $model;
        }

        $instance->load();

        return $instance;
    }

    public function resolve(Controller $controller, string $method): array
    {
        $args = [];
        $missing = [];

        $container = $this->container;

        $rMethod = new ReflectionMethod($controller, $method);
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
                if (is_subclass_of($type, ModelInterface::class)) {
                    $value = $this->resolveModel($type);
                } else {
                    $value = $container->has($name) ? $container->get($name) : $container->get($type);
                }
            } elseif (str_ends_with($name, 'Service')) {
                $value = $container->get($name);
            } elseif ($this->request->has($name)) {
                $value = $this->request->get($name, $type === 'array' ? [] : '');
            } elseif ($rParameter->isDefaultValueAvailable()) {
                $value = $rParameter->getDefaultValue();
            } elseif (count($rParameters) === 1 && ($name === 'id' || str_ends_with($name, '_id'))) {
                $value = $this->request->getId($name);
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