<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Restriction;

use ManaPHP\Persistence\RestrictionInterface;
use ManaPHP\Query\QueryInterface;
use function is_array;

class Eq implements RestrictionInterface
{
    public function __construct(protected string $field, protected mixed $value)
    {
    }

    public function apply(QueryInterface $query): void
    {
        if (is_array($this->value)) {
            $query->whereIn($this->field, $this->value);
        } else {
            $query->whereEq($this->field, $this->value);
        }
    }
}