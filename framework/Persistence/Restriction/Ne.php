<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Restriction;

use ManaPHP\Persistence\RestrictionInterface;
use ManaPHP\Query\QueryInterface;
use function is_array;

class Ne implements RestrictionInterface
{
    public function __construct(protected string $field, protected mixed $value)
    {
    }

    public function apply(QueryInterface $query): void
    {
        if (is_array($this->value)) {
            $query->whereNotIn($this->field, $this->value);
        } else {
            $query->whereCmp($this->field, '!=', $this->value);
        }
    }
}