<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

interface RespondedFilterInterface
{
    public function onResponded(): void;
}