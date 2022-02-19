<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

interface BeginFilterInterface
{
    public function onBegin(): void;
}