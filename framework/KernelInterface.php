<?php
declare(strict_types=1);

namespace ManaPHP;

interface KernelInterface
{
    public function boot(): void;
}