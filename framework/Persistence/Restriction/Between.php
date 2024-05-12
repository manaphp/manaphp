<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Restriction;

use ManaPHP\Persistence\RestrictionInterface;
use ManaPHP\Query\QueryInterface;

class Between implements RestrictionInterface
{
    public function __construct(protected string $field, protected mixed $min, protected mixed $max)
    {
    }

    public function apply(QueryInterface $query): void
    {
        $query->whereBetween($this->field, $this->min, $this->max);
    }
}