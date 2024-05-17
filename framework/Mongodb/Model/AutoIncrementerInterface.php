<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb\Model;

interface AutoIncrementerInterface
{
    public function getNext(string $entityClass, int $step = 1): int;
}