<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors\CoroutineStats;

class ExportRequestMessage
{
    public function __construct(public int $cid)
    {

    }
}