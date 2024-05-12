<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Restriction;

use ManaPHP\Persistence\RestrictionInterface;
use ManaPHP\Query\QueryInterface;

class Lt implements RestrictionInterface
{
    public function __construct(protected string $field, protected mixed $value)
    {
    }

    public function apply(QueryInterface $query): void
    {
        $query->whereCmp($this->field, '<', $this->value);
    }
}