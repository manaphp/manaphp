<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors\RequestsTotal;

class MetricUpdatedMessage
{
    public function __construct(public int $code, public string $handler)
    {

    }
}