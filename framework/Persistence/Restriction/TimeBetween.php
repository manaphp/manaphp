<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Restriction;

use ManaPHP\Persistence\RestrictionInterface;
use ManaPHP\Query\QueryInterface;

class TimeBetween implements RestrictionInterface
{
    public function __construct(protected string $field, protected mixed $min, protected mixed $max)
    {
    }

    public function apply(QueryInterface $query): void
    {
        $query->whereDateBetween($this->field, $this->min, $this->max);
    }
}