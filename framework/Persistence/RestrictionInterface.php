<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Query\QueryInterface;

interface RestrictionInterface
{
    public function apply(QueryInterface $query): void;
}