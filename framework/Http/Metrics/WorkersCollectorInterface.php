<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics;

interface WorkersCollectorInterface extends CollectorInterface
{
    public function querying(): array;
}