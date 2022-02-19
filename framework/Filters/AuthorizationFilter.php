<?php
declare(strict_types=1);

namespace ManaPHP\Filters;

use ManaPHP\Http\Filter;
use ManaPHP\Http\Filter\AuthorizingFilterInterface;

/**
 * @property-read \ManaPHP\Http\AuthorizationInterface $authorization
 */
class AuthorizationFilter extends Filter implements AuthorizingFilterInterface
{
    public function onAuthorizing(): void
    {
        $this->authorization->authorize();
    }
}