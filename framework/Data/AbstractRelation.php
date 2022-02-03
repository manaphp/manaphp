<?php
declare(strict_types=1);

namespace ManaPHP\Data;

abstract class AbstractRelation implements RelationInterface
{
    protected string $thisModel;
    protected string $thatModel;

    public function getThatQuery(): QueryInterface
    {
        /** @var \ManaPHP\Data\ModelInterface $referenceModel */
        $referenceModel = $this->thatModel;

        return $referenceModel::select();
    }
}