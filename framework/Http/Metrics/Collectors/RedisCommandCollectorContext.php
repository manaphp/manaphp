<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

class RedisCommandCollectorContext
{
    public array $commands = [];

    public string $command;
    public float $start_time;
}