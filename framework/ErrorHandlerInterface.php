<?php
declare(strict_types=1);

namespace ManaPHP;

use Throwable;

interface ErrorHandlerInterface
{
    public function handle(Throwable $throwable): void;
}