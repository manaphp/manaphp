<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

interface ConnectionMakerInterface
{
    public function make(array $parameters): mixed;
}