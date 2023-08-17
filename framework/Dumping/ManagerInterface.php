<?php
declare(strict_types=1);

namespace ManaPHP\Dumping;

interface ManagerInterface
{
    public function dump(object $object): array;
}