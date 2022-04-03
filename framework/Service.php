<?php
declare(strict_types=1);

namespace ManaPHP;

use ManaPHP\Logging\Logger\LogCategorizable;

class Service extends Component implements LogCategorizable
{
    public function categorizeLog(): string
    {
        return basename(str_replace('\\', '.', static::class), 'Service');
    }
}