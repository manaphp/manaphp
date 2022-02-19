<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

interface EndFilterInterface
{
    public function onEnd(): void;
}