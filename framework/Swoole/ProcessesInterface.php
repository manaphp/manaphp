<?php
declare(strict_types=1);

namespace ManaPHP\Swoole;

use ManaPHP\BootstrapperInterface;

interface ProcessesInterface extends BootstrapperInterface
{
    public function bootstrap(): void;
}