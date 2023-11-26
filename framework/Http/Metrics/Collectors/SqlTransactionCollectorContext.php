<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

class SqlTransactionCollectorContext
{
    public array $transactions = [];

    public float $start_time;
}