<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model\Relation;

use ManaPHP\Data\Model\AbstractRelation;
use ManaPHP\Data\ModelInterface;
use ManaPHP\Data\ModelManagerInterface;
use ManaPHP\Data\QueryInterface;
use ManaPHP\Helper\Arr;
use ManaPHP\Helper\Container;

class HasManyToMany extends AbstractRelation
{
    protected string $selfField;
    protected string $thatField;
    protected string $pivotModel;
    protected string $selfPivot;
    protected string $thatPivot;

    public function __construct(string $selfModel, string $thatModel, string $pivotModel)
    {
        $modelManager = Container::get(ModelManagerInterface::class);

        $this->selfModel = $selfModel;
        $this->selfField = $modelManager->getPrimaryKey($selfModel);
        $this->thatModel = $thatModel;
        $this->thatField = $modelManager->getPrimaryKey($thatModel);
        $this->pivotModel = $pivotModel;
        $this->selfPivot = $modelManager->getReferencedKey($selfModel);
        $this->thatPivot = $modelManager->getPrimaryKey($thatModel);
    }

    public function earlyLoad(array $r, QueryInterface $query, string $name): array
    {
        /** @var \ManaPHP\Data\ModelInterface $pivotModel */
        $pivotModel = $this->pivotModel;
        $selfPivot = $this->selfPivot;
        $thatPivot = $this->thatPivot;

        $ids = Arr::unique_column($r, $this->selfField);
        $pivotQuery = $pivotModel::select([$this->selfPivot, $this->thatPivot])->whereIn($this->selfPivot, $ids);
        $pivot_data = $pivotQuery->execute();
        $ids = Arr::unique_column($pivot_data, $this->thatPivot);
        $data = $query->whereIn($this->thatField, $ids)->indexBy($this->thatField)->fetch();

        $rd = [];
        foreach ($pivot_data as $dv) {
            $key = $dv[$thatPivot];

            if (isset($data[$key])) {
                $rd[$dv[$selfPivot]][] = $data[$key];
            }
        }

        foreach ($r as $ri => $rv) {
            $rvr = $rv[$selfPivot];
            $r[$ri][$name] = $rd[$rvr] ?? [];
        }

        return $r;
    }

    public function lazyLoad(ModelInterface $instance): QueryInterface
    {
        /** @var \ManaPHP\Data\ModelInterface $pivotModel */
        /** @var \ManaPHP\Data\ModelInterface $thatModel */
        $thatModel = $this->thatModel;
        $selfField = $this->selfField;
        $pivotModel = $this->pivotModel;

        $ids = $pivotModel::values($this->thatPivot, [$this->selfPivot => $instance->$selfField]);
        return $thatModel::select()->whereIn($this->thatField, $ids)->setFetchType(true);
    }
}
