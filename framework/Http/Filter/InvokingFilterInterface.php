<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

interface InvokingFilterInterface
{
    public function onInvoking(): void;
}