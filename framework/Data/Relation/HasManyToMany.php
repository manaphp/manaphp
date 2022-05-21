<?php
declare(strict_types=1);

namespace ManaPHP\Data\Relation;

use ManaPHP\Data\AbstractRelation;
use ManaPHP\Data\Model\ManagerInterface;
use ManaPHP\Data\ModelInterface;
use ManaPHP\Data\QueryInterface;
use ManaPHP\Helper\Arr;
use ManaPHP\Helper\Container;

class HasManyToMany extends AbstractRelation
{
    protected string $thisField;
    protected string $thatField;
    protected string $pivotModel;
    protected string $thisPivot;
    protected string $thatPivot;

    public function __construct(string $thisModel, string $thatModel, string $pivotModel)
    {
        $modelManager = Container::get(ManagerInterface::class);

        $this->thisModel = $thisModel;
        $this->thisField = $modelManager->getPrimaryKey($thisModel);
        $this->thatModel = $thatModel;
        $this->thatField = $modelManager->getPrimaryKey($thatModel);
        $this->pivotModel = $pivotModel;
        $this->thisPivot = $modelManager->getForeignedKey($thisModel);
        $this->thatPivot = $modelManager->getPrimaryKey($thatModel);
    }

    public function earlyLoad(array $r, QueryInterface $query, string $name): array
    {
        /** @var \ManaPHP\Data\ModelInterface $pivotModel */
        $pivotModel = $this->pivotModel;
        $thisPivot = $this->thisPivot;
        $thatPivot = $this->thatPivot;

        $ids = Arr::unique_column($r, $this->thisField);
        $pivotQuery = $pivotModel::select([$this->thisPivot, $this->thatPivot])->whereIn($this->thisPivot, $ids);
        $pivot_data = $pivotQuery->execute();
        $ids = Arr::unique_column($pivot_data, $this->thatPivot);
        $data = $query->whereIn($this->thatField, $ids)->indexBy($this->thatField)->fetch();

        $rd = [];
        foreach ($pivot_data as $dv) {
            $key = $dv[$thatPivot];

            if (isset($data[$key])) {
                $rd[$dv[$thisPivot]][] = $data[$key];
            }
        }

        foreach ($r as $ri => $rv) {
            $rvr = $rv[$thisPivot];
            $r[$ri][$name] = $rd[$rvr] ?? [];
        }

        return $r;
    }

    public function lazyLoad(ModelInterface $instance): QueryInterface
    {
        /** @var \ManaPHP\Data\ModelInterface $pivotModel */
        /** @var \ManaPHP\Data\ModelInterface $thatModel */
        $thatModel = $this->thatModel;
        $thisField = $this->thisField;
        $pivotModel = $this->pivotModel;

        $ids = $pivotModel::values($this->thatPivot, [$this->thisPivot => $instance->$thisField]);
        return $thatModel::select()->whereIn($this->thatField, $ids)->setFetchType(true);
    }
}
