<?php
declare(strict_types=1);

namespace ManaPHP\Model;

use ManaPHP\Query\QueryInterface;

abstract class AbstractRelation implements RelationInterface
{
    protected string $selfModel;
    protected string $thatModel;

    public function getThatQuery(): QueryInterface
    {
        /** @var \ManaPHP\Model\ModelInterface $referenceModel */
        $referenceModel = $this->thatModel;

        return $referenceModel::select();
    }
}