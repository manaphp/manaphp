<?php
declare(strict_types=1);

namespace ManaPHP\Cli\Command;

use ManaPHP\Cli\Command;
use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Invoking\ArgumentsResolverInterface as ResolverInterface;

class ArgumentsResolver extends Component implements ArgumentsResolverInterface
{
    #[Inject] protected ResolverMakerInterface $resolverMaker;

    #[Value] protected array $resolvers = ['option'];
    protected ResolverInterface $resolver;

    public function __construct()
    {
        $this->resolver = $this->resolverMaker->make(['resolvers' => $this->resolvers]);
    }

    public function resolve(Command $command, string $method): array
    {
        return $this->resolver->resolve($command, $method);
    }
}