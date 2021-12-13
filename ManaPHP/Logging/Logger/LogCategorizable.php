<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger;

interface LogCategorizable
{
    public function categorizeLog(): string;
}