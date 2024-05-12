<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Restriction;

use ManaPHP\Persistence\RestrictionInterface;
use ManaPHP\Query\QueryInterface;

class EndsWith implements RestrictionInterface
{
    public function __construct(protected string|array $fields, protected string $value)
    {
    }

    public function apply(QueryInterface $query): void
    {
        $query->whereEndsWith($this->fields, $this->value);
    }
}