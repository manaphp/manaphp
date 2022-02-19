<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Logging\Logger\LogCategorizable;

class Filter extends Component implements LogCategorizable
{
    public function categorizeLog(): string
    {
        return str_replace('\\', '.', static::class);
    }
}