<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Logging\Logger\LogCategorizable;

class Command extends Component implements LogCategorizable
{
    #[Inject] protected ConsoleInterface $console;

    public function categorizeLog(): string
    {
        return basename(str_replace('\\', '.', static::class), 'Command');
    }
}