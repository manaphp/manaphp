<?php
declare(strict_types=1);

namespace ManaPHP\Persistence\Attribute;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Persistence\EntityMetadataInterface;
use ManaPHP\Query\QueryInterface;

abstract class AbstractRelation implements RelationInterface
{
    #[Autowired] protected EntityMetadataInterface $entityMetadata;

    #[Autowired] protected string $selfEntity = '';
    #[Autowired] protected string $thatEntity = '';

    public function getThatQuery(): QueryInterface
    {
        return $this->entityMetadata->getRepository($this->thatEntity)->select();
    }
}