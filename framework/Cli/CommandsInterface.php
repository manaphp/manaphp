<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

interface CommandsInterface
{
    public function getCommands(): array;
}