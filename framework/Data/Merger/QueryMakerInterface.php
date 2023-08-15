<?php
declare(strict_types=1);

namespace ManaPHP\Data\Merger;

interface QueryMakerInterface
{
    public function make(string $query): mixed;
}