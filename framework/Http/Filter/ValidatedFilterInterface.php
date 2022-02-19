<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

interface ValidatedFilterInterface
{
    public function onValidated(): void;
}