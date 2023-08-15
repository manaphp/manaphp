<?php
declare(strict_types=1);

namespace ManaPHP\Http\Client;

interface EngineMakerInterface
{
    public function make(string $engine): mixed;
}