<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

interface ManagerInterface
{
    public function register(): void;
}