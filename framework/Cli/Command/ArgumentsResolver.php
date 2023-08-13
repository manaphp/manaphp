<?php
declare(strict_types=1);

namespace ManaPHP\Cli\Command;

use ManaPHP\Cli\Command;
use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Invoking\ArgumentsResolverInterface as Resolver;

class ArgumentsResolver extends Component implements ArgumentsResolverInterface
{
    #[Inject] protected MakerInterface $maker;

    protected array $resolvers;
    protected Resolver $resolver;

    public function __construct(array $resolvers = ['option'])
    {
        $this->resolvers = $resolvers;
        $this->resolver = $this->maker->make(Resolver::class, ['resolvers' => $resolvers]);
    }

    public function resolve(Command $command, string $method): array
    {
        return $this->resolver->resolve($command, $method);
    }
}