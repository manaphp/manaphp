<?php
declare(strict_types=1);

namespace ManaPHP\Data\Relation;

use ManaPHP\Data\ModelInterface;
use ManaPHP\Data\QueryInterface;
use ManaPHP\Data\RelationInterface;

interface ManagerInterface
{
    public function has(string $model, string $name): bool;

    public function get(string $model, string $name): false|RelationInterface;

    public function earlyLoad(string $model, array $r, array $withs): array;

    public function lazyLoad(ModelInterface $instance, string $relation_name): QueryInterface;

    public function getQuery(string $model, string $name, array $data): QueryInterface;
}
