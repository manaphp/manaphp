<?php
declare(strict_types=1);

namespace ManaPHP\Invoking;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Validating\Constraint\Attribute\Required;
use ManaPHP\Validating\Constraint\Attribute\Type;
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
        $rMethod = new ReflectionMethod($controller, $method);

        if (($numOfParameters = $rMethod->getNumberOfParameters()) === 0) {
            return [];
        }

        $args = \array_fill(0, $numOfParameters, null);

        $rParameters = $rMethod->getParameters();

        $numOfObjects = 0;
        foreach ($rParameters as $i => $rParameter) {
            if (($rType = $rParameter->getType()) !== null && !$rType->isBuiltin()) {
                $numOfObjects++;
                $name = $rParameter->getName();
                $type = $rType->getName();
                if (\is_subclass_of($type, ArgumentResolvable::class)) {
                    $args[$i] = $type::argumentResolve($this->container);
                } else {
                    $args[$i] = $this->resolveObjectValue($rParameter, $type, $name) ?? $this->container->get($type);
                }
            }
        }

        if ($numOfObjects !== $numOfParameters) {
            $validation = $this->validator->beginValidate([]);
            foreach ($args as $i => $value) {
                if ($value !== null) {
                    continue;
                }

                $rParameter = $rParameters[$i];

                $name = $rParameter->getName();
                $rType = $rParameter->getType();
                $type = $rType?->getName();

                $validation->field = $name;
                $validation->value = $this->resolveScalarValue($rParameter, $type, $name);
                if ($validation->value === null) {
                    if ($rParameter->isDefaultValueAvailable()) {
                        $args[$i] = $rParameter->getDefaultValue();
                    } elseif (!$rType->allowsNull()) {
                        $validation->validate(new Required());
                    }
                    continue;
                }

                if ($validation->validate(new Type($type))) {
                    $args[$i] = $validation->value;
                }
            }
            $this->validator->endValidate($validation);
        }

        return $args;
    }
}