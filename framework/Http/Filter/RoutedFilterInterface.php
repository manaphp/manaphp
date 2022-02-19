<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

interface RoutedFilterInterface
{
    public function onRouted(): void;
}