<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Query\QueryInterface;

interface RelationsInterface
{
    public function has(string $entityClass, string $name): bool;

    public function get(string $entityClass, string $name): ?RelationInterface;

    public function earlyLoad(string $entityClass, array $r, array $withs): array;

    public function lazyLoad(Entity $entity, string $relation_name): QueryInterface;

    public function getQuery(string $entityClass, string $name, array $data): QueryInterface;
}
