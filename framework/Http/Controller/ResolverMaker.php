<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Invoking\ArgumentsResolverInterface as ResolverInterface;

class ResolverMaker implements ResolverMakerInterface
{
    #[Inject] protected MakerInterface $maker;

    public function make(array $parameters): mixed
    {
        return $this->maker->make(ResolverInterface::class, $parameters);
    }
}