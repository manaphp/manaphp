<?php
declare(strict_types=1);

namespace ManaPHP\Model;

use ManaPHP\Query\QueryInterface;

interface RelationsInterface
{
    public function has(string $model, string $name): bool;

    public function get(string $model, string $name): ?RelationInterface;

    public function earlyLoad(string $model, array $r, array $withs): array;

    public function lazyLoad(ModelInterface $instance, string $relation_name): QueryInterface;

    public function getQuery(string $model, string $name, array $data): QueryInterface;
}
