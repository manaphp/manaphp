<?php
declare(strict_types=1);

namespace ManaPHP\Invoking;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Validating\Rule\Attribute\Required;
use ManaPHP\Validating\Rule\Attribute\Type;
use ManaPHP\Validating\ValidatorInterface;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use ReflectionParameter;

class ArgumentsResolver implements ArgumentsResolverInterface
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected ValidatorInterface $validator;

    #[Autowired] protected array $resolvers = [];

    /**
     * @var ScalarValueResolverInterface[]
     */
    protected array $scalarValueResolvers = [];

    /**
     * @var ObjectValueResolverInterface[]
     */
    protected array $objectValueResolvers = [];

    /** @noinspection PhpTypedPropertyMightBeUninitializedInspection */
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

        $rMethod = new ReflectionMethod($controller, $method);
        $rParameters = $rMethod->getParameters();
        foreach ($rParameters as $rParameter) {
            $name = $rParameter->getName();
            $value = null;

            $type = $rParameter->getType();
            if ($type !== null) {
                $type = $type->getName();
            } elseif ($rParameter->isDefaultValueAvailable()) {
                $type = \gettype($rParameter->getDefaultValue());
            }

            if ($type !== null && str_contains($type, '\\')) {
                if (\is_subclass_of($type, ArgumentResolvable::class)) {
                    $value = $type::argumentResolve($this->container);
                } else {
                    $value = $this->resolveObjectValue($rParameter, $type, $name) ?? $this->container->get($type);
                }
            } elseif (($value = $this->resolveScalarValue($rParameter, $type, $name)) !== null) {
                null;
            } elseif ($rParameter->isDefaultValueAvailable()) {
                $value = $rParameter->getDefaultValue();
            } elseif ($type === 'NULL') {
                $value = null;
            }

            if ($value === null && $type !== 'NULL') {
                if ($rParameter->hasType() && !$rParameter->getType()?->allowsNull()) {
                    $missing[] = $name;
                } else {
                    $args[] = null;
                }

                continue;
            }

            switch ($type) {
                case 'boolean':
                case 'bool':
                    $value = $this->validator->validateValue($name, $value, [new Type('bool')]);
                    break;
                case 'integer':
                case 'int':
                    $value = $this->validator->validateValue($name, $value, [new Type('int')]);
                    break;
                case 'double':
                case 'float':
                    $value = $this->validator->validateValue($name, $value, [new Type('float')]);
                    break;
                case 'string':
                    $value = (string)$value;
                    break;
                case 'array':
                    $value = \is_string($value) ? explode(',', $value) : (array)$value;
                    break;
            }

            $args[] = $value;
        }

        if ($missing) {
            $validation = $this->validator->beginValidate([]);
            $validation->value = null;

            foreach ($missing as $field) {
                $validation->field = $field;
                $validation->validate(new Required());
            }
            $this->validator->endValidate($validation);
        }

        return $args;
    }
}