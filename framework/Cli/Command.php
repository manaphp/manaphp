<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

use ManaPHP\Component;
use ManaPHP\Logging\Logger\LogCategorizable;

/**
 * @property-read \ManaPHP\Cli\ConsoleInterface $console
 */
class Command extends Component implements LogCategorizable
{
    public function categorizeLog(): string
    {
        return basename(str_replace('\\', '.', static::class), 'Command');
    }
}