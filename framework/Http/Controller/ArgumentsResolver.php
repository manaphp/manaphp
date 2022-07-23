<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller;

use ManaPHP\Component;
use ManaPHP\Di\FactoryInterface;
use ManaPHP\Http\Controller;
use ManaPHP\Invoking\ArgumentsResolverInterface as Resolver;

class ArgumentsResolver extends Component implements ArgumentsResolverInterface
{
    protected array $resolvers;
    protected Resolver $resolver;

    public function __construct(FactoryInterface $factory,
        array $resolvers = ['model', 'identity', 'session', 'request']
    ) {
        $this->resolvers = $resolvers;
        $this->resolver = $factory->make(Resolver::class, ['resolvers' => $resolvers]);
    }

    public function resolve(Controller $controller, string $method): array
    {
        return $this->resolver->resolve($controller, $method);
    }
}