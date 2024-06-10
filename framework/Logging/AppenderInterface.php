<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

interface AppenderInterface
{
    public function append(Log $log): void;
}