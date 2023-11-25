<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics;

class Histogram
{
    public array $buckets = [];
    public float $sum = 0;
    public int $count = 0;

    public function __construct(array $buckets)
    {
        foreach ($buckets as $bucket) {
            $this->buckets[$bucket] = 0;
        }
    }

    public function update(float $v): void
    {
        foreach ($this->buckets as $le => $_) {
            if ($v <= $le) {
                $this->buckets[$le]++;
            }
        }

        $this->sum += $v;
        $this->count++;
    }
}