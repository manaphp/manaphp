<?php
declare(strict_types=1);

namespace ManaPHP\Data\Mongodb;

interface ConnectionMakerInterface
{
    public function make(array $parameters): mixed;
}