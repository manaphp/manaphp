<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors\ResponseSize;

class ExportResponseMessage
{
    public function __construct(public array $histograms)
    {

    }
}