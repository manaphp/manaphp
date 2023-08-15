<?php
declare(strict_types=1);

namespace ManaPHP\Cli\Command;

use ManaPHP\Cli\Command;
use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Invoking\ArgumentsResolverInterface as ResolverInterface;

class ArgumentsResolver extends Component implements ArgumentsResolverInterface
{
    #[Inject] protected ResolverMakerInterface $resolverMaker;

    protected array $resolvers;
    protected ResolverInterface $resolver;

    public function __construct(array $resolvers = ['option'])
    {
        $this->resolvers = $resolvers;
        $this->resolver = $this->resolverMaker->make(['resolvers' => $resolvers]);
    }

    public function resolve(Command $command, string $method): array
    {
        return $this->resolver->resolve($command, $method);
    }
}