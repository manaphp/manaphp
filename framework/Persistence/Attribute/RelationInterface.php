<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Attribute;

use ManaPHP\Persistence\Entity;
use ManaPHP\Query\QueryInterface;

interface RelationInterface extends Transiently
{
    public function earlyLoad(array $r, QueryInterface $thatQuery, string $name): array;

    public function lazyLoad(Entity $entity): QueryInterface;
}