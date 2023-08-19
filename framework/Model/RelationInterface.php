<?php
declare(strict_types=1);

namespace ManaPHP\Model;

use ManaPHP\Query\QueryInterface;

interface RelationInterface
{
    public function earlyLoad(array $r, QueryInterface $query, string $name): array;

    public function lazyLoad(ModelInterface $instance): QueryInterface;
}