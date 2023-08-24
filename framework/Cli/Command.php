<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

use ManaPHP\Di\Attribute\Inject;

class Command
{
    #[Inject] protected ConsoleInterface $console;
}