<?php
declare(strict_types=1);

namespace ManaPHP\Invoking;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Model\ModelManagerInterface;
use ManaPHP\Validating\Validator\ValidateFailedException;
use ManaPHP\Validating\ValidatorInterface;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use ReflectionParameter;

class ArgumentsResolver implements ArgumentsResolverInterface
{
    #[Inject] protected ContainerInterface $container;
    #[Inject] protected ValidatorInterface $validator;
    #[Inject] protected RequestInterface $request;
    #[Inject] protected ModelManagerInterface $modelManager;

    #[Value] protected array $resolvers = [];

    /**
     * @var ScalarValueResolverInterface[]
     */
    protected array $scalarValueResolvers = [];

    /**
     * @var ObjectValueResolverInterface[]
     */
    protected array $objectValueResolvers = [];

    public function __construct()
    {
        foreach ($this->resolvers as $resolver) {
            if (!str_contains($resolver, '\\')) {
                $resolver = __NAMESPACE__ . '\\ValueResolver\\' . ucfirst($resolver);
            }

            $instance = $this->container->get($resolver);

            if ($instance instanceof ScalarValueResolverInterface) {
                $this->scalarValueResolvers[] = $instance;
            }

            if ($instance instanceof ObjectValueResolverInterface) {
                $this->objectValueResolvers[] = $instance;
            }
        }
    }

    protected function resolveObjectValue(ReflectionParameter $parameter, ?string $type, string $name): mixed
    {
        foreach ($this->objectValueResolvers as $resolver) {
            if (($value = $resolver->resolve($parameter, $type, $name)) !== null) {
                return $value;
            }
        }

        return null;
    }

    protected function resolveScalarValue(ReflectionParameter $parameter, ?string $type, string $name): mixed
    {
        foreach ($this->scalarValueResolvers as $resolver) {
            if (($value = $resolver->resolve($parameter, $type, $name)) !== null) {
                return $value;
            }
        }

        return null;
    }

    public function resolve(object $controller, string $method): array
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
                $value = $this->resolveObjectValue($rParameter, $type, $name) ?? $container->get($type);
            } elseif (($value = $this->resolveScalarValue($rParameter, $type, $name)) !== null) {
                null;
            } elseif ($rParameter->isDefaultValueAvailable()) {
                $value = $rParameter->getDefaultValue();
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
}