<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

interface StringifyFilterInterface
{
    public function onStringify(): void;
}