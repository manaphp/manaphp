<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model;

use ManaPHP\Data\QueryInterface;

abstract class AbstractRelation implements RelationInterface
{
    protected string $selfModel;
    protected string $thatModel;

    public function getThatQuery(): QueryInterface
    {
        /** @var \ManaPHP\Data\ModelInterface $referenceModel */
        $referenceModel = $this->thatModel;

        return $referenceModel::select();
    }
}