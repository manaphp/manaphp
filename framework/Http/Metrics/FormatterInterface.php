<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics;

interface FormatterInterface
{
    public const GAUGE = 1;
    public const COUNTER = 2;

    public function gauge(string $name, int|array $value, array $labels = [], array $levels = [],): string;

    public function counter(string $name, int|array $value, array $labels = [], array $levels = [],): string;

    public function number(string $name, int|array $value, array $labels = [], array $levels = []): string;

    public function histogram(string $name, Histogram $histogram, array $labels): string;
}