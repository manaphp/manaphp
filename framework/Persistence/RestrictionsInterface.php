<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

interface RestrictionsInterface
{
    public function get(): array;
}