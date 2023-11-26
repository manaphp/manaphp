<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics;

interface SimpleCollectorInterface extends CollectorInterface
{
    public function updating(?string $handler): ?array;

    public function updated(array $data): void;

    public function querying(): mixed;
}