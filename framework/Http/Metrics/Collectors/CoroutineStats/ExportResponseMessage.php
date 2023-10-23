<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors\CoroutineStats;

class ExportResponseMessage
{
    public function __construct(public int $cid, public array $stats)
    {

    }
}