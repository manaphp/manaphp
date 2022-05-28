<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model;

use ManaPHP\Data\ModelInterface;
use ManaPHP\Data\QueryInterface;

interface RelationInterface
{
    public function earlyLoad(array $r, QueryInterface $query, string $name): array;

    public function lazyLoad(ModelInterface $instance): QueryInterface;
}