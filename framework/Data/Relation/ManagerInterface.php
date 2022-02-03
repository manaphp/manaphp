<?php
declare(strict_types=1);

namespace ManaPHP\Data\Relation;

use ManaPHP\Data\ModelInterface;
use ManaPHP\Data\QueryInterface;
use ManaPHP\Data\RelationInterface;

interface ManagerInterface
{
    public function has(ModelInterface $model, string $name): bool;

    public function get(ModelInterface $model, string $name): false|RelationInterface;

    public function earlyLoad(ModelInterface $model, array $r, array $withs): array;

    public function lazyLoad(ModelInterface $instance, string $relation_name): QueryInterface;

    public function getQuery(ModelInterface $model, string $name, array $data): QueryInterface;
}
