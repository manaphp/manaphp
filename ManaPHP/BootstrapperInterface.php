<?php
declare(strict_types=1);

namespace ManaPHP;

interface BootstrapperInterface
{
    public function bootstrap(): void;
}