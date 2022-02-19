<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

interface AuthenticatingFilterInterface
{
    public function onAuthenticating(): void;
}