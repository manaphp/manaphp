<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Restriction;

use ManaPHP\Persistence\RestrictionInterface;
use ManaPHP\Query\QueryInterface;

class Mod implements RestrictionInterface
{
    public function __construct(protected string $field, protected int $divisor, protected int $remainder)
    {
    }

    public function apply(QueryInterface $query): void
    {
        $query->whereMod($this->field, $this->divisor, $this->remainder);
    }
}