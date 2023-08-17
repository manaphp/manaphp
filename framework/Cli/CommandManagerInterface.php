<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

interface CommandManagerInterface
{
    public function getCommands(): array;
}