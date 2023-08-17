<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller;

use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Http\Controller;
use ManaPHP\Invoking\ArgumentsResolverInterface as ResolverInterface;

class ArgumentsResolver extends Component implements ArgumentsResolverInterface
{
    #[Inject] protected MakerInterface $maker;

    #[Value] protected array $resolvers = ['model', 'identity', 'session', 'request'];

    protected ResolverInterface $resolver;

    public function __construct()
    {
        $this->resolver = $this->maker->make(ResolverInterface::class, ['resolvers' => $this->resolvers]);
    }

    public function resolve(Controller $controller, string $method): array
    {
        return $this->resolver->resolve($controller, $method);
    }
}