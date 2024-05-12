<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Restriction;

use ManaPHP\Persistence\RestrictionInterface;
use ManaPHP\Query\QueryInterface;

class Nin implements RestrictionInterface
{
    public function __construct(protected string $field, protected array $values)
    {
    }

    public function apply(QueryInterface $query): void
    {
        $query->whereNotIn($this->field, $this->values);
    }
}