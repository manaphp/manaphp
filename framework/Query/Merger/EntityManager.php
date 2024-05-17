<?php
declare(strict_types=1);

namespace ManaPHP\Query\Merger;

use ManaPHP\Di\Attribute\Config;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Persistence\AbstractEntityManager;
use ManaPHP\Persistence\Entity;
use ManaPHP\Query\QueryInterface;

class EntityManager extends AbstractEntityManager
{
    #[Config] protected string $queryClass = 'ManaPHP\Query\Merger\Query';

    protected function newQuery(): QueryInterface
    {
        return $this->maker->make($this->queryClass);
    }

    public function create(Entity|array $entity): Entity
    {
        throw new NotSupportedException(__METHOD__);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public static function insert(array $record): int
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function update(array|Entity $entity): Entity
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function delete(Entity $entity): Entity
    {
        throw new NotSupportedException(__METHOD__);
    }
}