<?php
declare(strict_types=1);

namespace ManaPHP\Dumping;

interface DumperInterface
{
    public function dump(object $object): array;
}