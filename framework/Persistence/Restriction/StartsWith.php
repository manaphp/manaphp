<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Restriction;

use ManaPHP\Persistence\RestrictionInterface;
use ManaPHP\Query\QueryInterface;

class StartsWith implements RestrictionInterface
{
    public function __construct(protected string|array $field, protected string $value, protected ?int $length = null)
    {
    }

    public function apply(QueryInterface $query): void
    {
        $query->whereStartsWith($this->field, $this->value, $this->length);
    }
}