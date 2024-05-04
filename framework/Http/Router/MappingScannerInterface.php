<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router;

use ManaPHP\BootstrapperInterface;

interface MappingScannerInterface extends BootstrapperInterface
{
    public function scan(): void;
}