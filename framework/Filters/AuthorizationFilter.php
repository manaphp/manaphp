<?php
declare(strict_types=1);

namespace ManaPHP\Filters;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Http\AuthorizationInterface;
use ManaPHP\Http\Filter;
use ManaPHP\Http\Filter\AuthorizingFilterInterface;

class AuthorizationFilter extends Filter implements AuthorizingFilterInterface
{
    #[Inject] protected AuthorizationInterface $authorization;

    public function onAuthorizing(): void
    {
        $this->authorization->authorize();
    }
}