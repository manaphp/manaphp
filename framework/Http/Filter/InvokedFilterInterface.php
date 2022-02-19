<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

interface InvokedFilterInterface
{
    public function onInvoked(): void;
}