<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

class SqlStatementCollectorContext
{
    public array $statements = [];

    public string $statement;
    public float $start_time;
}