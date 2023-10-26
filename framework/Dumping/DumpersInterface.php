<?php
declare(strict_types=1);

namespace ManaPHP\Dumping;

interface DumpersInterface
{
    public function dump(object $object): array;
}