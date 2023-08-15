<?php
declare(strict_types=1);

namespace ManaPHP\Http\Request;

interface FileMakerInterface
{
    public function make(array $parameters): mixed;
}