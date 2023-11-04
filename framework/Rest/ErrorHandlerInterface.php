<?php
declare(strict_types=1);

namespace ManaPHP\Rest;

use Throwable;

interface ErrorHandlerInterface
{
    public function handle(Throwable $throwable): void;
}