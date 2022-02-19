<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

interface AuthorizedFilterInterface
{
    public function onAuthorized(): void;
}