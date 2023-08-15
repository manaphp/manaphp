<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Client;

interface EngineMakerInterface
{
    public function make(array $parameters): mixed;
}