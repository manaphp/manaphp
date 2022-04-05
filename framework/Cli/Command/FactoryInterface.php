<?php
declare(strict_types=1);

namespace ManaPHP\Cli\Command;

use ManaPHP\Cli\Command;

interface FactoryInterface
{
    public function get(string $command): Command;
}