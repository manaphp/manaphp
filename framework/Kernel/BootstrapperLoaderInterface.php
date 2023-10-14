<?php
declare(strict_types=1);

namespace ManaPHP\Kernel;

interface BootstrapperLoaderInterface
{
    public function load(): void;
}