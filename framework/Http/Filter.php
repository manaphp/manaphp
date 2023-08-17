<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Logging\Logger\LogCategorizable;

class Filter implements LogCategorizable
{
    public function categorizeLog(): string
    {
        return str_replace('\\', '.', static::class);
    }
}