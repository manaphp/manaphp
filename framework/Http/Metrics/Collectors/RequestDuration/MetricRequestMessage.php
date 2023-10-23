<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors\RequestDuration;

class MetricRequestMessage
{
    public function __construct(public string $path, public float $elapsed)
    {

    }
}