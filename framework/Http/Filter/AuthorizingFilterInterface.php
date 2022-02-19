<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

interface AuthorizingFilterInterface
{
    public function onAuthorizing(): void;
}