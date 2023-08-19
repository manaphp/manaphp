<?php
declare(strict_types=1);

namespace ManaPHP\Db;

interface PreparedEmulatorInterface
{
    public function emulate(string $sql, array $bind, int $preservedStrLength = -1): string;
}