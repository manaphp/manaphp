<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Attribute;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\Container;
use ManaPHP\Persistence\EntityMetadataInterface;
use ManaPHP\Query\QueryInterface;

abstract class AbstractRelation implements RelationInterface
{
    #[Autowired] protected string $selfEntity = '';
    #[Autowired] protected string $thatEntity = '';

    public function getThatQuery(): QueryInterface
    {
        $repository = Container::get(EntityMetadataInterface::class)->getRepository($this->thatEntity);
        return $repository->select();
    }
}