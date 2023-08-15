<?php
declare(strict_types=1);

namespace ManaPHP\Coroutine;

interface TaskMakerInterface
{
    public function make(array $parameters): mixed;
}