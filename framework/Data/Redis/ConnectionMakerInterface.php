<?php
declare(strict_types=1);

namespace ManaPHP\Data\Redis;

interface ConnectionMakerInterface
{
    public function make(array $parameters): mixed;
}