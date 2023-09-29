<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

use ManaPHP\Di\Attribute\Autowired;

class Command
{
    #[Autowired] protected ConsoleInterface $console;
}