<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics;

interface CollectorInterface
{
    public function export(mixed $data): string;
}