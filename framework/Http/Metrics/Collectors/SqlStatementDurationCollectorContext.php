<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

class SqlStatementDurationCollectorContext
{
    public array $statements = [];
}