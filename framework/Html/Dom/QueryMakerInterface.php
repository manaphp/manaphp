<?php
declare(strict_types=1);

namespace ManaPHP\Html\Dom;

interface QueryMakerInterface
{
    public function make(array $parameters): mixed;
}