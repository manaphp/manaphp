<?php
declare(strict_types=1);

namespace ManaPHP\Invoking\ValueResolver;

use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Identifying\IdentityInterface;
use ManaPHP\Invoking\ScalarValueResolverInterface;

class Identity extends Component implements ScalarValueResolverInterface
{
    #[Inject]
    protected IdentityInterface $identity;

    public function resolve(?string $type, string $name): mixed
    {
        return $this->identity->hasClaim($name) ? $this->identity->getClaim($name) : null;
    }
}