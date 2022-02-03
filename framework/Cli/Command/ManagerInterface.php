<?php
declare(strict_types=1);

namespace ManaPHP\Cli\Command;

interface ManagerInterface
{
    public function getCommands(): array;
}