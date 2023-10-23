<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors\RequestsTotal;

class ExportResponseMessage
{
    public function __construct(public array $totals)
    {

    }
}