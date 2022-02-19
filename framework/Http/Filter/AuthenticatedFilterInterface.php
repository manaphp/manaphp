<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

interface AuthenticatedFilterInterface
{
    public function onAuthenticated(): void;
}