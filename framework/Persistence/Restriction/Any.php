<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Restriction;

use ManaPHP\Persistence\RestrictionInterface;
use ManaPHP\Query\QueryInterface;

class Any implements RestrictionInterface
{
    public function __construct(protected array $filters)
    {
    }

    public function apply(QueryInterface $query): void
    {
        $query->where($this->filters);
    }
}