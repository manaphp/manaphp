<?php
declare(strict_types=1);

namespace ManaPHP\Dumping;

interface DumperManagerInterface
{
    public function dump(object $object): array;
}