<?php
declare(strict_types=1);

namespace ManaPHP\Kernel;

interface ConfigLoaderInterface
{
    public function load(): void;
}