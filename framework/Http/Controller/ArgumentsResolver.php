<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller;

use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Http\Controller;
use ManaPHP\Invoking\ArgumentsResolverInterface as ResolverInterface;

class ArgumentsResolver extends Component implements ArgumentsResolverInterface
{
    #[Inject] protected ResolverMakerInterface $resolverMaker;

    protected array $resolvers;
    protected ResolverInterface $resolver;

    public function __construct(array $resolvers = ['model', 'identity', 'session', 'request']
    ) {
        $this->resolvers = $resolvers;
        $this->resolver = $this->resolverMaker->make(['resolvers' => $resolvers]);
    }

    public function resolve(Controller $controller, string $method): array
    {
        return $this->resolver->resolve($controller, $method);
    }
}