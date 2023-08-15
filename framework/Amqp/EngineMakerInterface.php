<?php
declare(strict_types=1);

namespace ManaPHP\Amqp;

interface EngineMakerInterface
{
    public function make(array $parameters): mixed;
}