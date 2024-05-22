<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Query\QueryInterface;

interface EntityManagerInterface
{
    public function query(string $entityClass): QueryInterface;

    public function create(Entity $entity): Entity;

    public function update(Entity $entity, Entity $original): Entity;

    public function delete(Entity $entity): Entity;
}
