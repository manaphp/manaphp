<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

class PoolsBusyTotalCollectorContext
{
    public array $busy_totals = [];
    public array $pop_totals = [];
}