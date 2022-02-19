<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

interface RoutingFilterInterface
{
    public function onRouting(): void;
}