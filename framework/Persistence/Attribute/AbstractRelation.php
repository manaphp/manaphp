<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Attribute;

use ManaPHP\Helper\Container;
use ManaPHP\Persistence\EntityMetadataInterface;
use ManaPHP\Query\QueryInterface;

abstract class AbstractRelation implements RelationInterface
{
    protected string $selfEntity;
    protected string $thatEntity;

    public function getThatQuery(): QueryInterface
    {
        $repository = Container::get(EntityMetadataInterface::class)->getRepository($this->thatEntity);
        return $repository->select();
    }
}