<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors\ResponseSize;

class MetricUpdatedMessage
{
    public function __construct(public string $path, public int $size)
    {

    }
}