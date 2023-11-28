<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics;

interface WorkersDataInterface
{
    public function get(string $collector, float $timeout = 1.0): array;
}