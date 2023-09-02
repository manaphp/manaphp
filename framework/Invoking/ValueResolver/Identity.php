<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Identifying\IdentityInterface;
use ManaPHP\Invoking\ScalarValueResolverInterface;
use ReflectionParameter;

class Identity implements ScalarValueResolverInterface
{
    #[Inject] protected IdentityInterface $identity;

    public function resolve(ReflectionParameter $parameter, ?string $type, string $name): mixed
    {
        return $this->identity->hasClaim($name) ? $this->identity->getClaim($name) : null;
    }
}