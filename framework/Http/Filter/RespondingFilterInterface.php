<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

interface RespondingFilterInterface
{
    public function onResponding(): void;
}